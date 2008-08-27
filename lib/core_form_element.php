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

abstract class core_form_element {

	private $attribs     = array();
	private $required    = false;
	private $allow_empty = true;
	private $validators  = array();


	// Constructor

	public function __construct($id) {
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


	// Validation

	public function add_validator($validator) {
		$this->validators[$validator] = true;
	}

	public function add_validators($validators) {
		$this->validators = array_merge($this->validators, $validators);
	}

	public function set_validators($validators) {
		$this->validators = $validators;
	}

	public function get_validator($name) {
		if(key_exists($this->validators, $name))
			return($this->validators[$name]);
		return(null);
	}

	public function get_validators() {
		return($this->validators);
	}

	public function remove_validator($name) {
		if(key_exists($this->validators, $name))
			unset($this->validators[$name]);
	}

	public function clear_validators() {
		$this->validators = array();
	}

	public function is_valid() {
		// vérifier si la valeur est valide
	}

	public function get_errors() {
		// retourne les erreurs
	}

	public function get_messages() {
		// retourne les messages
	}


	// Rendering

	abstract public function render();

}
