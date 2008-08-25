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
	
	public function loader($wf, $position) {
		$this->wf = $wf;
		$this->tpl = new core_tpl($this->wf);
	}

	public function set($name, $value=null) {
		$this->tpl->set($name, $value);
	}

	public function append($name, $value=null) {
		$this->tpl->apend($name, $value);
	}

	public function get($name) {
		return($this->tpl->get($name));
	}

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Lance le systeme de rendu et retourne les données
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function rendering($tpl_name) {
		echo $this->tpl->fetch($tpl_name);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Permet d'ajouter un managed body avec un template
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function add_managed_tpl($title, $core_tpl) {
		$data = null;
		if(file_exists($core_tpl->tpl_file))
			$data = file_get_contents($core_tpl->tpl_file);

		$tpl = new core_tpl($this->wf);
		$tpl->set('data', $data);
		$tpl->set('title', $title);
		$tpl->set('path', $core_tpl->tpl_file);
		$tpl->set('vars', $core_tpl->vars);

		echo $tpl->fetch('core_managed_body', TRUE);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Permet d'ajouter un managed body avec un buffer
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function add_managed_buffer($title, $buffer) {
		//
	}
	
	
}