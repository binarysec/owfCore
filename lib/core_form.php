<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * Web Framework 1                                       *
 * BinarySEC (c) (2000-2008) / www.binarysec.com         *
 * Author: Olivier Pascal <op@binarysec.com>             *
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

	private $wf              = null;
	private $attribs         = array();
	private $elements        = array();
	private $hidden_elements = array();
	private $tpl             = null;


	// Constructor

	public function __construct($wf, $id) {
		$this->wf = $wf;
		$this->id = $id;
		$this->tpl = new core_tpl($this->wf);

		$this->enctype = 'application/x-www-form-urlencoded';
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
		if($element->hidden)
			$this->hidden_elements[$element->id] = $element;
		else
			$this->elements[$element->id] = $element;
	}


	// Rendering

	protected function build_attribs($names) {
		$attribs = '';
		foreach($names as $name)
			if(array_key_exists($name, $this->attribs))
				$attribs .= ' '.$name.'="'.$this->attribs[$name].'"';
		return($attribs);
	}

	public function render($tpl_name) {
		$attribs = $this->build_attribs(array(
			'id',
			'name',
			'class',
			'action',
			'method',
			'enctype'
		));

		$this->tpl->set_vars($this->attribs);
		$this->tpl->set('form_attribs', $this->attribs);
		$this->tpl->set('form_attribs_string', $attribs);
		$this->tpl->set('form_elements', $this->elements);
		$this->tpl->set('form_hidden_elements', $this->hidden_elements);
		return($this->tpl->fetch($tpl_name));
	}

}


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

	protected function build_attribs($names) {
		$attribs = '';
		foreach($names as $name)
			if(array_key_exists($name, $this->attribs))
				$attribs .= ' '.$name.'="'.$this->attribs[$name].'"';
		return($attribs);
	}

	abstract public function render();

}


class core_form_hidden extends core_form_element {

	public function __construct($id) {
		parent::__construct($id);
		$this->hidden = true;
	}

	public function render() {
		$attribs = $this->build_attribs(array(
			'id',
			'name',
			'value'
		));
		return('<input type="hidden"'.$attribs.' />');
	}

}


class core_form_submit extends core_form_element {

	public function __construct($id) {
		parent::__construct($id);
	}

	public function render() {
		$attribs = $this->build_attribs(array(
			'id',
			'name',
			'value',
			'class',
			'disabled',
			'size'
		));
		return('<input type="submit"'.$attribs.' />');
	}

}


class core_form_reset extends core_form_element {

	public function __construct($id) {
		parent::__construct($id);
	}

	public function render() {
		$attribs = $this->build_attribs(array(
			'id',
			'name',
			'value',
			'class',
			'disabled',
			'size'
		));
		return('<input type="reset"'.$attribs.' />');
	}

}


class core_form_text extends core_form_element {

	public function __construct($id) {
		parent::__construct($id);
	}

	public function render() {
		$attribs = $this->build_attribs(array(
			'id',
			'name',
			'value',
			'class',
			'disabled',
			'maxlength',
			'readonly',
			'size'
		));
		return('<input type="text"'.$attribs.' />');
	}

}


class core_form_password extends core_form_element {

	public function __construct($id) {
		parent::__construct($id);
	}

	public function render() {
		$attribs = $this->build_attribs(array(
			'id',
			'name',
			'value',
			'class',
			'disabled',
			'maxlength',
			'readonly',
			'size'
		));
		return('<input type="password"'.$attribs.' />');
	}

}


class core_form_textarea extends core_form_element {

	public function __construct($id) {
		parent::__construct($id);
	}

	public function render() {
		$attribs = $this->build_attribs(array(
			'id',
			'name',
			'class',
			'disabled',
			'cols',
			'rows',
			'readonly'
		));
		return('<textarea'.$attribs.'>'.$this->value.'</textarea>');
	}

}


class core_form_button extends core_form_element {

	public function __construct($id) {
		parent::__construct($id);
	}

	public function render() {
		$attribs = $this->build_attribs(array(
			'id',
			'name',
			'value',
			'class',
			'disabled',
			'size'
		));
		return('<input type="button"'.$attribs.' />');
	}

}
