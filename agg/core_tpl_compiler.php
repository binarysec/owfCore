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

/**
 * Code fortement inspirée de la librairie jTpl
 * (compilateur de template) du framework PHP Jelix
 * Auteur original : Laurent Jouanneau
 * Modifiée par    : Loic Mathaud
 * Repris par      : Olivier Pascal
 * Licence         : GNU Lesser General Public Licence
 * Lien            : http://www.jelix.org
 */
class core_tpl_compiler extends wf_agg {
	
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

	private $allowed_in_var;
	private $allowed_in_expr;
	private $allowed_in_foreach;
	private $allowed_assign;

	private $block_stack = array();
	private $literals = array();
	private $plugins = array();

	private $current_tag;
	private $tpl_file;

	public function loader($wf) {
		$this->wf = $wf;

		$this->allowed_in_var  = array_merge($this->vartype, $this->op);
		$this->allowed_in_expr = array_merge($this->vartype, $this->op);
		$this->allowed_in_foreach = array_merge($this->vartype, array(T_AS, T_DOUBLE_ARROW));
		$this->alowed_assign = array_merge($this->vartype, $this->assign_op, $this->op);
	}

	public function compile($tpl_file, $tpl_cache) {
		$this->source_file = $tpl_file;

		/* compile the template */
		$tpl_content = file_get_contents($tpl_file);

		$res = preg_replace("!{\*(.*?)\*}!s", '', $tpl_content);
		$res = preg_replace("!<\?(.*?)\?>!s", '', $res);

		preg_match_all("!{literal}(.*?){/literal}!s", $res, $match);
		$this->literals = $match[1];

		$res = preg_replace("!{literal}(.*?){/literal}!s", '{literal}', $res);
		$res = preg_replace_callback("/{((.).*?)}/s", array($this, 'parse'), $res);

		$header = '<?php '."\n";
		foreach($this->plugins as $plugin => $flag) {
			$method = 'func_'.$plugin;
			$header .= 'function tpl_func_'.$plugin.$this->$method()."\n";
		}
		$header .= "\n".'?>'."\n";

		if(count($this->block_stack)) {
			throw new wf_exception(
				$this,
				WF_EXC_PRIVATE,
				'End block missing for <strong>'.end($this->block_stack).'</strong>.'
				.' in <strong>'.$this->source_file.'</strong>.'
			);
			return(false);
		}

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

		if(!preg_match('/^\$|[a-zA-Z\/]$/', $firstcar))
			throw new wf_exception(
				$this,
				WF_EXC_PRIVATE,
				'Invalid syntax for <strong>'.$tag.'</strong>'
				.' in <strong>'.$this->source_file.'</strong>.'
			);

		$this->current_tag = $tag;

		if ($firstcar == '$')
			return('<?php echo '.$this->parse_var($tag).'; ?>');
		elseif ($firstcar == '*')
			return('');
		else {
			if (!preg_match('/^(\/?[a-zA-Z0-9_]+)(?:(?:\s+(.*))|(?:\((.*)\)))?$/', $tag, $m)) {
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
			if($m[1] == 'ldelim')
				return('{');
			if($m[1] == 'rdelim')
				return('}');
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
				if (method_exists($this, 'func_'.$name)) {
					$argfct = $this->parse_final($args, $this->alowed_assign);
					$res = 'tpl_func_'.$name.'($t'.((trim($argfct) != '') ? ', '.$argfct : '').'); ';
					$this->plugins[$name] = true;
				}
				else {
					throw new wf_exception(
						$this,
						WF_EXC_PRIVATE,
						'Unknown tag <strong>'.$name.'</strong>'
						.' in <strong>'.$this->source_file.'</strong>.'
					);
				}
		}
		return($res);
	}

	private function parse_var($expr) {
		$res = $this->parse_final($expr, $this->allowed_in_var);
		return($res);
	}

	private function parse_final($string, $allowed=array()) {
		$result = '';
		$bracketcount = 0;
		$sqbracketcount = 0;

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


	// Plugin functions

	public function func_js() {
		return('($t, $file) { echo $t->wf->core_js()->linker($file); }');
	}

	public function func_css() {
		return('($t, $file) { echo $t->wf->core_css()->linker($file); }');
	}

	public function func_img() {
		return('($t, $file) { echo $t->wf->core_img()->linker($file); }');
	}

	public function func_p_js() {
		return('($t, $file) { echo $t->wf->core_js()->pass_linker($file); }');
	}

	public function func_p_css() {
		return('($t, $file) { echo $t->wf->core_css()->pass_linker($file); }');
	}

	public function func_p_img() {
		return('($t, $file) { echo $t->wf->core_img()->pass_linker($file); }');
	}

}
