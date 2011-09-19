<?php
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * Web Framework 1                                       *
 * BinarySEC (c) (2000-2008) / www.binarysec.com         *
 * Author: Michael Vergoz <mv@binarysec.com>             *
 * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~         *
 *  Avertissement : ce logiciel est protégé par la       *
 *  loi du copyright et par les traités internationaux.  *
 *  Toute personne ne respectant pas ces dispositions    *
 *  se rendra coupable du délit de contrefaçon et sera   *
 *  passible des sanctions pénales prévues par la loi.   *
 *  Il est notamment strictement interdit de décompiler, *
 *  désassembler ce logiciel ou de procèder à des        *
 *  opération de "reverse engineering".                  *
 *                                                       *
 *  Warning : this software product is protected by      *
 *  copyright law and international copyright treaties   *
 *  as well as other intellectual property laws and      *
 *  treaties. Is is strictly forbidden to reverse        *
 *  engineer, decompile or disassemble this software     *
 *  product.                                             *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

class core_tpl_compiler extends wf_agg {

	// Attributes

	private $vartype = array(
		T_CHARACTER,
		T_CONSTANT_ENCAPSED_STRING,
		T_DNUMBER,
		T_ENCAPSED_AND_WHITESPACE,
		T_LNUMBER,
		T_OBJECT_OPERATOR,
		T_STRING,
		T_WHITESPACE,
		T_ARRAY
	);

	private $assign_op = array(
		T_AND_EQUAL,
		T_DIV_EQUAL,
		T_MINUS_EQUAL,
		T_MOD_EQUAL,
		T_MUL_EQUAL,
		T_OR_EQUAL,
		T_PLUS_EQUAL,
		T_PLUS_EQUAL,
		T_SL_EQUAL,
		T_SR_EQUAL,
		T_XOR_EQUAL
	);

	private $op = array(
		T_BOOLEAN_AND,
		T_BOOLEAN_OR,
		T_EMPTY,
		T_INC,
		T_DEC,
		T_ISSET,
		T_IS_EQUAL,
		T_IS_GREATER_OR_EQUAL,
		T_IS_IDENTICAL,
		T_IS_NOT_EQUAL,
		T_IS_NOT_IDENTICAL,
		T_IS_SMALLER_OR_EQUAL,
		T_LOGICAL_AND,
		T_LOGICAL_OR,
		T_LOGICAL_XOR,
		T_SR,
		T_SL,
		T_DOUBLE_ARROW
	);

	private $php_exec = 1;
	private $allowed_func = array();
	private $registered_generator = array();
	private $ldelim = '%{';
	private $rdelim = '}%';

	private $modifiers = array(
		'upper'        => 'strtoupper',
		'lower'        => 'strtolower',
		'html'         => 'htmlspecialchars',
		'strip_tags'   => 'strip_tags',
		'escurl'       => 'rawurlencode',
		'capitalize'   => 'ucwords',
		'stripslashes' => 'stripslashes',
		'entities'     =>  array('htmlentities', 'ENT_COMPAT', '"UTF-8"'),
		'type'         => 'gettype',
		'nl2br'        => 'nl2br',
		'class_name'   => 'get_class',
		'count'        => 'count',
		'b64_encode'   => 'base64_encode',
		'b64_dcode'    => 'base64_decode',
		'escxml'       => 'htmlspecialchars',
		'utf8_decode'  => 'utf8_decode',
		'strlen'       => 'strlen'
	);

	private $allowed_in_var;
	private $allowed_in_expr;
	private $allowed_in_foreach;
	private $allowed_assign;

	private $block_stack = array();
	private $literals = array();

	private $current_tag;


	// Loader

	public function loader($wf) {
		$this->wf = $wf;
		
		$this->allowed_in_var  = array_merge($this->vartype, $this->op);
		$this->allowed_in_expr = array_merge($this->vartype, $this->assign_op, $this->op);
		$this->allowed_in_foreach = array_merge($this->vartype, array(T_AS, T_DOUBLE_ARROW));
		$this->alowed_assign = array_merge($this->vartype, $this->assign_op, $this->op);

		/* load global tpl functions */
		$this->load_tpl_funcs();
	}


	// Compiler

	public function register($name, $callback) {
		$this->registered_generator[$name] = $callback;
	}

	public function add_modifier($name, $func) {
		$this->modifiers[$name] = $func;
	}

	public function compile($tpl_name, $tpl_file, $tpl_cache, $ld=null, $rd=null, $php_exec, $allowed_func, $registered_generator) {
		$this->source_file = $tpl_file;

		$this->php_exec = $php_exec;
		$this->allowed_func = $allowed_func;
		$this->registered_generator = array_merge($this->registered_generator, $registered_generator);
		
		if(is_null($ld) || is_null($rd)) {
			$ld = $this->ldelim;
			$rd = $this->rdelim;
		}
		
		/* compile the template */
		$tpl_content = file_get_contents($tpl_file);
		
		$res = preg_replace('!'.$ld.'\*(.*?)\*'.$rd.'!s', '', $tpl_content);
		$res = preg_replace("!<\?(.*?)\?>!s", '', $res);
		
		preg_match_all('!'.$ld.'literal'.$rd.'(.*?)'.$ld.'/literal'.$rd.'!s', $res, $match);
		$this->literals = $match[1];
		
		$res = preg_replace('!'.$ld.'literal'.$rd.'(.*?)'.$ld.'/literal'.$rd.'!s', $ld.'literal'.$rd, $res);
		$body = preg_replace_callback('/'.$ld.'((.).*?)'.$rd.'/s', array($this, 'parse'), $res);
		
		/* generate a lang context for the template */
		$res = '<?php '.$this->get_headers($tpl_name).$this->func_alt_init().' ?>'.$body;
		
		if(count($this->block_stack)) {
			throw new wf_exception(
				$this,
				WF_EXC_PRIVATE,
				'End block missing for'.
				'<strong>'.end($this->block_stack).'</strong>.'
				.' in <strong>'.$this->source_file.'</strong>.'
			);
		}
		
		if(isset($header))
			$res = $header.$res;
		$res = preg_replace('/\?>\n?<\?php/', '', $res);
		$res = preg_replace('/<\?php\s*\?>/', '', $res);
		
		/* cache dir not found or not writable */
		if(!is_writable(dirname($tpl_cache))) {
			if(!is_dir(dirname($tpl_cache))) {
				throw new wf_exception(
					$this,
					WF_EXC_PRIVATE,
					'Cache directory <strong>'.dirname($tpl_cache).'</strong> not found.'
				);
				return(false);
			}
			throw new wf_exception(
				$this,
				WF_EXC_PRIVATE,
				'Cache directory <strong>'.dirname($tpl_cache).'</strong> not writable.'
			);
			return(false);
		}
		
		/* cache the template in file */
		file_put_contents($tpl_cache, $res);
		
		return(true);
	}

	private function parse($matches) {
		list(, $tag, $firstcar) = $matches;
		
		if(!preg_match('/^\$|@|[a-zA-Z\/]$/', $firstcar))
			throw new wf_exception(
				$this,
				WF_EXC_PRIVATE,
				'Invalid syntax for <strong>'.$tag.'</strong>'
				.' in <strong>'.$this->source_file.'</strong>.'
			);
		
		$this->current_tag = $tag;
		
		if($firstcar == '$')
			return('<?php echo '.$this->parse_var($tag).'; ?>');
		elseif($firstcar == '*')
			return('');
		else {
			if ($firstcar != '@'
			&& !preg_match('/^(\/?[a-zA-Z0-9_]+)(?:(?:\s+(.*))|(?:\((.*)\)))?$/', $tag, $m)) {
				throw new wf_exception(
					$this,
					WF_EXC_PRIVATE,
					'Invalid function <strong>'.$tag.'</strong>'
					.' in <strong>'.$this->source_file.'</strong>.'
				);
				return('');
			}
			if(count($m) == 4)
				$m[2] = $m[3];
			if(!isset($m[2]))
				$m[2] = '';
			
			if($firstcar == '@') {
				return('<?php '.$this->parse_function('lang', substr($tag, 1)).'?>');
			}
			else {
				if($m[1] == 'ldelim')
					return('{');
				if($m[1] == 'rdelim')
					return('}');
			}
			
			return('<?php '.$this->parse_function($m[1], $m[2]).'?>');
		}
	}

	private function parse_function($name, $args) {
		$res = '';
		switch($name) {
			case 'if':
				$res = 'if('.$this->parse_final($args, $this->allowed_in_expr).'):';
				array_push($this->block_stack, 'if');
				break;
			case 'else':
				if (end($this->block_stack) != 'if')
					throw new wf_exception(
						$this,
						WF_EXC_PRIVATE,
						'End tag missing for <strong>'.end($this->block_stack).'</strong>'
						.' in <strong>'.$this->source_file.'</strong>.'
					);
				else
					$res = 'else:';
				break;
			case 'elseif':
				if (end($this->block_stack) != 'if')
					throw new wf_exception(
						$this,
						WF_EXC_PRIVATE,
						'End tag missing for <strong>'.end($this->block_stack).'</strong>'
						.' in <strong>'.$this->source_file.'</strong>.'
					);
				else
					$res = 'elseif('.$this->parse_final($args, $this->allowed_in_expr).'):';
				break;
			case 'foreach':
				$res = 'foreach('.$this->parse_final($args, $this->allowed_in_foreach, array(';', '!')).'):';
				array_push($this->block_stack, 'foreach');
				break;
			case 'while':
				$res = 'while('.$this->parse_final($args, $this->allowed_in_expr).'):';
				array_push($this->block_stack, 'while');
				break;
			case 'for':
				$res = 'for('. $this->parse_final($args, $this->allowed_in_expr, array()) .'):';
				array_push($this->block_stack, 'for');
				break;
			case '/foreach':
			case '/for':
			case '/if':
			case '/while':
				$short = substr($name, 1);
				if(end($this->block_stack) != $short)
					throw new wf_exception(
						$this,
						WF_EXC_PRIVATE,
						'End tag missing for <strong>'.end($this->block_stack).'</strong>'
						.' in <strong>'.$this->source_file.'</strong>.'
					);
				else {
					array_pop($this->block_stack);
					$res = 'end'.$short.';';
				}
				break;
			case 'literal':
				if(count($this->literals))
					$res = '?>'.array_shift($this->literals).'<?php ';
				else
					throw new wf_exception(
						$this,
						WF_EXC_PRIVATE,
						'End tag missing for <strong>'.end($this->block_stack).'</strong>'
						.' in <strong>'.$this->source_file.'</strong>.'
					);
				break;
			case '/literal':
				throw new wf_exception(
					$this,
					WF_EXC_PRIVATE,
					'Begin tag missing for <strong>'.end($this->block_stack).'</strong>'
					.' in <strong>'.$this->source_file.'</strong>.'
				);
				break;
			default:
				$func = "func_$name";
				if(array_key_exists($name, $this->registered_generator)) {
					$argfct = $this->parse_final(
						$args,
						$this->alowed_assign
					);
					$argt = explode(',', $argfct);
					
					$buf_args = array();
					if(count($argt) == 1)
						$buf_args[] = $argfct;
					else {
						foreach($argt as $v)
							$buf_args[] = $v;
					}
					
					$res = $this->generator($this, $name, $buf_args);
				}
				else if(method_exists($this, $func)) {
					$argfct = $this->parse_final(
						$args,
						$this->alowed_assign
					);
					$argt = explode(',', $argfct);
					
					$buf_args = array();
					if(count($argt) == 1)
						$buf_args[] = $argfct;
					else {
						foreach($argt as $v)
							$buf_args[] = $v;
					}
					
					$res = $this->$func($this, $buf_args);
				}
				else {
					throw new wf_exception(
						$this,
						WF_EXC_PRIVATE,
						'Unknown tag <strong>'.$name.'</strong>'
						.' in <strong>'.$this->source_file.'</strong>.'
					);
				}
				break;
		}
		return($res);
	}

	private function parse_var($expr) {
		$tok = explode('|',$expr);
		$res = $this->parse_final(array_shift($tok), $this->allowed_in_var);
		
		foreach($tok as $modifier) {
			if(!preg_match('/^(\w+)(?:\:(.*))?$/', $modifier, $m)){
				throw new wf_exception(
					$this,
					WF_EXC_PRIVATE,
					'Invalid modifier <strong>'.$this->current_tag.'</strong>'
					.' in <strong>'.$this->source_file.'</strong>.'
				);
				return('');
			}
			
			$targs = array($res);

			$mod = $this->modifiers[$m[1]];
			if(isset($mod) && (is_array($mod) && count($mod) > 0 || !is_array($mod))) {
				if(is_array($mod)) {
					$res = $mod[0].'('.$res;
					for($i=1; $i<count($mod); $i++) {
						$res .= ', '.$mod[$i];
					}
					$res .= ')';
				}
				else {
					$res = $this->modifiers[$m[1]].'('.$res.')';
				}
			}
			else {
				throw new wf_exception(
					$this,
					WF_EXC_PRIVATE,
					'Unknown modifier <strong>'.$m[1].'</strong>'
					.' for <strong>'.$this->current_tag.'</strong>'
					.' in <strong>'.$this->source_file.'</strong>.'
				);
				return('');
			}
		}
		
		return($res);
	}

	private function parse_final($string, $allowed=array()) {
		$result = '';
		$bracketcount = 0;
		$sqbracketcount = 0;
		
		if($this->php_exec == 0) {
			$ret = preg_match_all('#([a-zA-Z0-9_]+)\([^(]*\)#', $string, $matches);
			foreach($matches[1] as $match) {
				if(!array_key_exists($match, $this->allowed_func)) {
					$string = preg_replace('#'.$match.'\([^(]*\)#', '$__', $string);
				}
			}
		}
		
		$tokens = token_get_all('<?php '.$string.'?>');
		
		if (array_shift($tokens) == '<' && $tokens[0] == '?' && is_array($tokens[1])
		    && $tokens[1][0] == T_STRING && $tokens[1][1] == 'php') {
			array_shift($tokens);
			array_shift($tokens);
		}
		
		foreach($tokens as $tok) {
			if (is_array($tok)) {
				list($type, $str) = $tok;
				
				if($type == T_CLOSE_TAG)
					continue;
				elseif($type == T_VARIABLE)
					$result .= '$t->vars[\''.substr($str, 1).'\']';
				elseif($type == T_WHITESPACE || in_array($type, $allowed))
					$result .= $str;
				else {
					throw new wf_exception(
						$this,
						WF_EXC_PRIVATE,
						'Invalid syntax for <strong>'.$this->current_tag.'</strong>'
						.' in <strong>'.$this->source_file.'</strong>.'
					);
					return('');
				}
			}
			else {
				if($tok =='(')
					$bracketcount++;
				elseif($tok ==')')
					$bracketcount--;
				elseif($tok =='[')
					$sqbracketcount++;
				elseif($tok ==']')
					$sqbracketcount--;
				$result .= $tok;
			}
		}
		
		if($bracketcount != 0 || $sqbracketcount !=0)
			throw new wf_exception(
				$this,
				WF_EXC_PRIVATE,
				'Bracket error for <strong>'.$this->current_tag.'</strong>'
				.' in <strong>'.$this->source_file.'</strong>.'
			);
		
		return($result);
	}


	// Plugins

	public function get_headers($tpl_name) {
		return('$_lang = $t->wf->core_lang()->get_context("tpl/'.$tpl_name.'");');
	}

	/* add js file */
	public function func_js(core_tpl_compiler $tpl_compiler, $argv) {
		return('$this->wf->core_html()->add_js('.$argv[0].');');
	}

	/* add css file */
	public function func_css(core_tpl_compiler $tpl_compiler, $argv) {
		return('$this->wf->core_html()->add_css('.$argv[0].');');
	}

	/* linker */
	public function func_link(core_tpl_compiler $tpl_compiler, $argv) {
		$args = $argv[0];
		if(isset($argv[1])) {
			$args .= ', null, '.$argv[1];
			if(isset($argv[2])) $args .= ', '.$argv[2];
		}
		return('echo htmlentities($this->wf->linker('.$args.'));');
	}

	/* translate */
	public function func_lang(core_tpl_compiler $tpl_compiler, $argv) {
		$buf_args = 'array(';
		foreach($argv as $v)
			$buf_args .= $v.',';
		$buf_args .= ')';
		return('echo $_lang->ts('.$buf_args.');');
	}

	/* alternating text */
	public function func_alt(core_tpl_compiler $tpl_compiler, $argv) {
		if(isset($argv[1])) {
			return('$alt = !$alt; echo ($alt) ? '.$argv[0].' : '.$argv[1].';');
		}
		return('$alt = !$alt; echo ($alt) ? '.$argv[0].' : \'\';');
	}

	/* init alternating text */
	public function func_alt_init() {
		return('$alt = false;');
	}

	/* assign */
	public function func_set(core_tpl_compiler $tpl_compiler, $argv) {
		return('$t->vars[\''.$argv[0].'\'] = '.$argv[1].';');
	}

	/* format date */
	public function func_date(core_tpl_compiler $tpl_compiler, $argv) {
		$time = isset($argv[1]) ? ', '.$argv[1] : '';
		return('echo date('.$argv[0].$time.');');
	}

	/* increment var */
	public function func_inc(core_tpl_compiler $tpl_compiler, $argv) {
		return('$t->vars['.$argv[0].']++;');
	}

	/* cut var */
	public function func_cut(core_tpl_compiler $tpl_compiler, $argv) {
		$etc = !$argv[2] ? 'if(strlen('.$argv[0].') > '.$argv[1].') echo \'(...)\';' : '';
		return('echo substr('.$argv[0].', 0, '.$argv[1].'); '.$etc);
	}

	/* random value */
	public function func_rand(core_tpl_compiler $tpl_compiler, $argv) {
		return('echo rand('.$argv[0].', '.$argv[1].');');
	}

	/* human readable size by keo on 22/01/09 */
	public function func_hsize(core_tpl_compiler $tpl_compiler, $argv) {
		return('echo $t->wf->bit8_scale('.$argv[0].');');
	}

	private function parse_tpl_var($var) {
		return(trim($var, '"\' '));
	}

	public function parse_tpl_args($args) {
		$params = array();
		foreach($args as $arg) {
			$parts = explode(',', $arg);
			foreach($parts as $part) {
				$atoms = explode('=', $part);
				if(count($atoms) == 1)
					$params[] = $this->parse_tpl_var($atoms[0]);
				else if(count($atoms) == 2)
					$params[$this->parse_tpl_var($atoms[0])] = $this->parse_tpl_var($atoms[1]);
			}
		}
		return($params);
	}
	
	public function generator(core_tpl_compiler $tpl_compiler, $name, $argv) {
		$class = $this->registered_generator[$name][0];
		$method = $this->registered_generator[$name][1];
		
		if(is_object($class)) {
			return('?>'.call_user_func($this->registered_generator[$name], $argv).'<?php');
		}
		else {
			return('?>'.$this->wf->$class()->$method($argv).'<?php');
		}
	}

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * This function will load global tpl functions
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	private function load_tpl_funcs() {
		foreach($this->wf->modules as $mod => $mod_infos) {
			/* check if module has hookable event */
			if(method_exists($mod_infos[8], "core_tpl_generator")) {
				$funcs = $mod_infos[8]->core_tpl_generator();

				foreach($funcs as $name => $func) {
					$insert = array(
						$func["agg"],
						$func["method"]
					);
					$this->registered_generator[$name] = $insert;
				}
			}
		}
	}

}
