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
		if($element->type == 'hidden')
			$this->hidden_elements[$element->id] = $element;
		else
			$this->elements[$element->id] = $element;
	}


	// Rendering

	protected function build_attribs($names) {
		$attribs = '';
		foreach($names as $name)
			if(array_key_exists($name, $this->attribs)) {
				if(is_bool($this->attribs[$name]))
					$value = $this->attribs[$name] ? 'true' : 'false';
				else
					$value = $this->attribs[$name];
				$attribs .= ' '.$name.'="'.$value.'"';
			}
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
		$this->tpl->set('form_is_valid', $this->is_valid());
		$this->tpl->set('form_is_submitted', $this->is_submitted());
		$this->tpl->set('form_attribs', $this->attribs);
		$this->tpl->set('form_attribs_string', $attribs);
		$this->tpl->set('form_elements', $this->elements);
		$this->tpl->set('form_hidden_elements', $this->hidden_elements);

		return($this->tpl->fetch($tpl_name));
	}


	// Validation

	public function is_valid() {
		if(!$this->is_submitted())
			return(false);

		foreach($this->elements as $id => $element)
			if(!$element->is_valid())
				return(false);

		return(true);
	}


	// Populating / Retrieving

	public function is_submitted() {
		foreach($this->elements as $id => $element)
			if($element->required
			&& is_null($this->get_value($element->name)))
				return(false);

		return(true);
	}	

	public function get_values() {
		$values = array();
		foreach($this->elements as $id => $element)
			$values[$element->name] = $this->get_value($element->name);
		return($values);
	}

	public function get_value($name) {
		if(strtolower($this->method) == 'post'
		&& array_key_exists($name, $_POST))
			return($_POST[$name]);
		else if(strtolower($this->method) == 'get'
		&& array_key_exists($name, $_GET))
			return($_GET[$name]);
		return(null);
	}

	public function populate($data) {
		/** TODO **/
	}

	public function reset() {
		/** TODO **/
	}

}


abstract class core_form_element {

	private $attribs     = array();
	private $required    = false;
	private $allow_empty = true;
	private $validators  = array();
	private $errors      = array();


	// Constructor

	public function __construct($id) {
		$this->id = $id;
	}


	// Properties

	public function __set($name, $value) {
		$this->attribs[$name] = $value;
		if($name == 'id' && !array_key_exists('name', $this->attribs))
			$this->attribs['name'] = $value;
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
		foreach($this->validators as $validator => $flag)
			switch($validator) {
				case 'email':
					if(!filter_var($value, FILTER_VALIDATE_EMAIL)) {
						$this->errors[] = 'Email non valide';
						return(false);
					}
					break;
				default:
					return(false);
			}
		return(true);
	}

	public function get_errors() {
		/** TODO **/
	}

	public function get_messages() {
		/** TODO **/
	}


	// Rendering

	protected function build_attribs($names) {
		$attribs = '';
		foreach($names as $name)
			if(array_key_exists($name, $this->attribs)) {
				if(is_bool($this->attribs[$name]))
					$value = $this->attribs[$name] ? 'true' : 'false';
				else
					$value = $this->attribs[$name];
				$attribs .= ' '.$name.'="'.$value.'"';
			}
		return($attribs);
	}

	abstract public function render();

}


class core_form_hidden extends core_form_element {

	public function __construct($id) {
		parent::__construct($id);
		$this->type = 'hidden';
		$this->required = true;
	}

	public function render() {
		$attribs = $this->build_attribs(array(
			'type',
			'id',
			'name',
			'value',
			'accesskey'
		));
		return('<input'.$attribs.' />');
	}

}


class core_form_submit extends core_form_element {

	public function __construct($id) {
		parent::__construct($id);
		$this->type = 'submit';
	}

	public function render() {
		$attribs = $this->build_attribs(array(
			'type',
			'id',
			'name',
			'value',
			'class',
			'disabled',
			'size',
			'style',
			'tabindex',
			'accesskey'
		));
		return('<input'.$attribs.' />');
	}

}


class core_form_reset extends core_form_element {

	public function __construct($id) {
		parent::__construct($id);
		$this->type = 'reset';
	}

	public function render() {
		$attribs = $this->build_attribs(array(
			'type',
			'id',
			'name',
			'value',
			'class',
			'disabled',
			'size',
			'style',
			'tabindex',
			'accesskey'
		));
		return('<input'.$attribs.' />');
	}

}


class core_form_text extends core_form_element {

	public function __construct($id) {
		parent::__construct($id);
		$this->type = 'text';
		$this->required = true;
	}

	public function render() {
		$attribs = $this->build_attribs(array(
			'type',
			'id',
			'name',
			'value',
			'class',
			'disabled',
			'maxlength',
			'readonly',
			'size',
			'style',
			'tabindex',
			'accesskey'
		));
		return('<input'.$attribs.' />');
	}

}


class core_form_password extends core_form_element {

	public function __construct($id) {
		parent::__construct($id);
		$this->type = 'password';
		$this->required = true;
	}

	public function render() {
		$attribs = $this->build_attribs(array(
			'type',
			'id',
			'name',
			'value',
			'class',
			'disabled',
			'maxlength',
			'readonly',
			'size',
			'style',
			'tabindex',
			'accesskey'
		));
		return('<input'.$attribs.' />');
	}

}


class core_form_textarea extends core_form_element {

	public function __construct($id) {
		parent::__construct($id);
		$this->required = true;
	}

	public function render() {
		$attribs = $this->build_attribs(array(
			'id',
			'name',
			'class',
			'disabled',
			'cols',
			'rows',
			'readonly',
			'style',
			'tabindex',
			'accesskey'
		));
		return('<textarea'.$attribs.'>'.$this->value.'</textarea>');
	}

}


class core_form_button extends core_form_element {

	public function __construct($id) {
		parent::__construct($id);
		$this->type = 'button';
	}

	public function render() {
		$attribs = $this->build_attribs(array(
			'type',
			'id',
			'name',
			'value',
			'class',
			'disabled',
			'size',
			'style',
			'tabindex',
			'accesskey'
		));
		return('<input'.$attribs.' />');
	}

}


class core_form_select extends core_form_element {

	public function __construct($id) {
		parent::__construct($id);
		$this->required = true;
	}

	private function construct_tree($options) {
		$buf = '';
		foreach($options as $label => $group) {
			if(is_array($group)) {
				$buf .= '<optgroup label="'.$label.'">';	
				$buf .= $this->construct_tree($group);
				$buf .= '</optgroup>';
			}
			else {
				$buf .= '<option>'.$group.'</option>'."\n";
			}
		}
		return($buf);
	}

	public function render() {
		$attribs = $this->build_attribs(array(
			'id',
			'class',
			'disabled',
			'multiple',
			'size',
			'style',
			'tabindex',
			'accesskey'
		));

		$name = $this->name;
		if($this->multiple)
			$name .= '[]';

		$buf  = '<select name="'.$name.'"'.$attribs.'>';
		foreach($this->options as $label => $group) {
			if(is_array($group)) {
				$buf .= '<optgroup label="'.$label.'">';	
				foreach($group as $value => $name) {
					if($value == $this->selected)
						$selected = ' selected="selected"';
					else
						$selected = '';
					$buf .= '<option
					  name="'.$this->name.'[]"
					  value="'.$value.'"
					  '.$selected.'>';
					$buf .= $name;
					$buf .= '</option>';
				}
				$buf .= '</optgroup>';
			}
			else {
				if($label == $this->selected)
					$selected = ' selected="selected"';
				else
						$selected = '';
				$buf .= '<option
				  name="'.$this->name.'[]"
				  value="'.$label.'"
				  '.$selected.'>';
				$buf .= $group;
				$buf .= '</option>';
			}
		}
		$buf .= '</select>';
		return($buf);
	}

}


class core_form_multiselect extends core_form_select {

	public function __construct($id) {
		parent::__construct($id);
		$this->multiple = 'multiple';
	}

}


class core_form_radio extends core_form_element {

	public function __construct($id) {
		parent::__construct($id);
		$this->type = 'radio';
		$this->required = true;
	}

	public function render() {
		$attribs = $this->build_attribs(array(
			'type',
			'id',
			'class',
			'disabled',
			'readonly',
			'size',
			'style',
			'tabindex',
			'accesskey'
		));

		$name = $this->name;
		if($this->type == 'checkbox')
			$name .= '[]';

		$buf = '';
		foreach($this->options as $value => $label) {
			if(is_array($this->selected)
			&& in_array($value, $this->selected)
			|| !is_array($this->selected)
			&& $value == $this->selected)
				$checked = ' checked="checked"';
			else
				$checked = '';
			$buf .= '<input'.$attribs.'
			  name="'.$name.'"
			  value="'.$value.'"
			  '.$checked.' />';
			$buf .= $label;	
		}
		return($buf);
	}

}


class core_form_checkbox extends core_form_radio {

	public function __construct($id) {
		parent::__construct($id);
		$this->type = 'checkbox';
	}

}


abstract class core_form_validator {

	private $errors = array();

	protected function add_error($error) {
		$this->errors[] = $error;
	}

	public function get_errors() {
		return($this->errors);
	}

	abstract public function is_valid($value);

}


class core_form_validate_email extends core_form_validator {

	public function is_valid($value) {
		$value = filter_var($value, FILTER_VALIDATE_EMAIL);

		if(!$value) {
			$this->add_error('Email non valide');
			return(true);
		}

		return(true);
	}

}
