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

class core_form {

	private $wf       = null;
	private $attribs  = array();
	private $elements = array();


	// Constructor

	public function __construct($wf, $id) {
		$this->wf = $wf;
		$this->id = $id;
	}


	// Properties

	public function __set($name, $value) {
		$this->attribs[$name] = $value;
	}

	public function __get($name) {
		if(array_key_exists($name, $this->attribs))
			return($this->attribs[$name]);
		return(null);
	}


	public function add_element($element) {
		$this->elements[$element->id] = $element;
	}


	// Rendering

	public function render($tpl_name) {
		$tpl = new core_tpl($this->wf);
		$tpl->set_vars($this->attribs);
		$tpl->set('elements', $this->elements);
		return($tpl->fetch($tpl_name));
	}

}
