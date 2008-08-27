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

class core_html extends wf_agg {
	var $tpl;
	
	var $_core_request;
	
	public function loader($wf, $position) {
		$this->wf = $wf;
		$this->_core_request = $this->wf->core_request();
		$this->set_title("Core module default HTML title");
	}

	/* * * * * * * * * * * * * * * * * * * * * * * * *
	 * Traitement du titre
	 * * * * * * * * * * * * * * * * * * * * * * * * */
	var $title = NULL;
	public function set_title($text) {
		$this->title = $text;
	}

	public function get_title() {
		return($this->title);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * *
	 * Traitement des metas
	 * http://www.i18nguy.com/markup/metatags.html
	 * * * * * * * * * * * * * * * * * * * * * * * * */
	var $meta_equiv = array();
	var $meta_name = array();
	
	public function set_meta_http_equiv($name, $value=array()) {
		$this->meta_equiv[$name] = $value;
	}
	
	public function set_meta_name($name, $value=array()) {
		$this->meta_name[$name] = $value;
	}
	
	public function get_meta() {
		$final = NULL;
		
		/* fait les meta equiv */
		foreach($this->meta_equiv as $name => $value) {
			$final .= "<meta http-equiv=\"$name\"";
			foreach($value as $k => $v ) 
				$final .= " $k=\"$v\"";
			$final .= "/>\n";
		}
		
		/* fait les meta name */
		foreach($this->meta_name as $name => $value) {
			$final .= "<meta name=\"$name\"";
			foreach($value as $k => $v ) 
				$final .= " $k=\"$v\"";
			$final .= "/>\n";
		}
		
		return($final);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * *
	 * Ajout d'une variable a appliqué sur le template 
	 * core_html
	 * * * * * * * * * * * * * * * * * * * * * * * * */
	var $vars = array();
	public function set($name, $value=null) {
		$this->vars[$name] = $value;
	}

	/* * * * * * * * * * * * * * * * * * * * * * * * *
	 * Append d'une variable a appliqué sur le template 
	 * core_html
	 * * * * * * * * * * * * * * * * * * * * * * * * */
	public function append($name, $value=null) {
		if(is_array($this->vars[$name]))
			$this->vars[$name] = array_merge(
				$this->vars[$name], 
				$value
			);
		else
			$this->vars[$name] .= $value;
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * *
	 * Prend une variable
	 * * * * * * * * * * * * * * * * * * * * * * * * */
	public function get($name) {
		return($this->vars[$name]);
	}

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Lance le systeme de rendu et retourne les données
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function rendering($body) {
		$tpl = new core_tpl($this->wf);
		
		/* hehe */
		if($this->_core_request->permissions["session:god"])
			$this->title .= " (as god)";
		else if($this->_core_request->permissions["session:admin"])
			$this->title .= " (as admin)";
		
		/* merge toute les variables */
		$tpl->merge_vars($this->vars);
		$tpl->merge_vars(array(
			'html_body' => $body,
			'html_title' => $this->title,
			'html_meta' => $this->get_meta()
		));
		
		echo $tpl->fetch('core_html');
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Permet d'ajouter un managed body avec un template
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function add_managed_tpl($title, $core_tpl) {
// 		$data = null;
// 		if(file_exists($core_tpl->tpl_file))
// 			$data = file_get_contents($core_tpl->tpl_file);
// 
// 		$tpl = new core_tpl($this->wf);
// 		$tpl->set('data', $data);
// 		$tpl->set('title', $title);
// 		$tpl->set('path', $core_tpl->tpl_file);
// 		$tpl->set('vars', $core_tpl->vars);
// 
// 		echo $tpl->fetch('core_managed_body', TRUE);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Permet d'ajouter un managed body avec un buffer
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function add_managed_buffer($title, $buffer) {
		//
	}
	
	
}