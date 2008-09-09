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
class core_tpl {

	// Attributes

	private $wf;
	private $a_core_html;
	private $a_core_tpl_compiler;
	private $cache_file;
	private $tpl_file;
	private $ldelim = null;
	private $rdelim = null;
	private $php_exec = 1;
	private $allowed_func = array();
	private $registered_generator = array();

	private $vars = array();


	// Constructor

	public function __construct($wf) {
		$this->wf = $wf;
		$this->a_core_html = $this->wf->core_html();
		$this->a_core_tpl_compiler = $this->wf->core_tpl_compiler();
	}

	// Accessors

	public function get_file() {
		return($this->tpl_file);
	}

	public function set($name, $value=null) {
		$this->vars[$name] = $value;
	}

	public function append($name, $value=null) {
		$this->vars[$name];
		
		if(is_array($this->vars[$name])) {
			$this->vars[$name] = array_merge(
				$this->vars[$name],
				$value
			);
		}
		else
			$this->vars[$name] .= $value;
	}

	public function get($name) {
		if(isset($this->vars[$name]))
			return($this->vars[$name]);
		return(null);
	}

	public function get_vars() {
		return($this->vars);
	}

	public function set_vars($vars) {
		$this->vars = $vars;
	}

	public function merge_vars($vars) {
		$this->vars = array_merge($this->vars, $vars);
	}
	
	public function locate($tpl_name) {
		$modrev = array_reverse($this->wf->modules);
		$cache_to = end($this->wf->modules);
		
		foreach($modrev as $mod => $mod_infos) {
			$tmp = $this->wf->modules[$mod][0].
				'/var/tpl/'.$tpl_name.'.tpl';
			if(file_exists($tmp)) {
				$this->tpl_file = $tmp;
				$this->cache_file = $cache_to[0].
					'/var/tpl_cache/'.$tpl_name.'.tpl';
				return(true);
			}
		}
		return(false);
	}

	public function get_template($tpl_name, $no_manage=FALSE) {
		/* locate the template */
		$found = $this->locate($tpl_name);

		/* if template doesn't exist */
		if(!$this->tpl_file) {
			throw new wf_exception(
				$this,
				WF_EXC_PRIVATE,
				"Can not find template $tpl_name."
			);
		}

		if(!$no_manage)
			$this->a_core_html->add_managed_tpl($tpl_name, &$this);

		if(!$this->tpl_file)
			return;

		if (!file_exists($this->cache_file) ||
		     filemtime($this->tpl_file) > filemtime($this->cache_file)) {
				
			$this->wf->create_dir($this->cache_file);
			
			$this->a_core_tpl_compiler->compile(
				$tpl_name, 
				$this->tpl_file, 
				$this->cache_file,
				$this->ldelim,
				$this->rdelim,
				$this->php_exec,
				$this->allowed_func,
				$this->registered_generator
			);
		}

		$t = $this;
		require($this->cache_file);
	}

	public function fetch($tpl_name, $no_manage=FALSE) {
		ob_start();
		$this->get_template($tpl_name, $no_manage);
		$content = ob_get_clean();
 		return($content);
	}

	public function set_delims($ldelim, $rdelim) {
		$this->ldelim = $ldelim;
		$this->rdelim = $rdelim;
	}

	public function set_phpexec($php_exec, $allowed_func=array()) {
		$this->php_exec = $php_exec;
		$this->allowed_func = $allowed_func;
	}
	
	public function register($name, $php_code) {
		$this->registered_generator[$name] = $php_code;
	}

}
