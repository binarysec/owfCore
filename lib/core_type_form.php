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

define('CORE_TYPE_FORM_VARCHAR',  800);
define('CORE_TYPE_FORM_NUM',      801);
define('CORE_TYPE_FORM_BOOL',     802);
define('CORE_TYPE_FORM_SELECT',   803);
define('CORE_TYPE_FORM_TEXT',     804);
define('CORE_TYPE_FORM_FILE',     805);

class core_type_form {

	private $buf = null;

	public function __construct($type, $data) {
		switch($type) {
			case CORE_TYPE_FORM_TEXT:
				$this->buf .= '<textarea>'.$data.'</textarea>';
				break;

			case CORE_TYPE_FORM_FILE:
				$this->buf .= '<input type="file" />';
				break;

			case CORE_TYPE_FORM_VARCHAR:
			case CORE_TYPE_FORM_NUM:
				$this->buf .= '<input type="text" value="'.$data.'" />';
				break;

			case CORE_TYPE_FORM_BOOL:
				$this->buf .= '<input type="radio" value="1" /> Oui';
				$this->buf .= '<input type="radio" value="0" /> Non';
				break;

			case CORE_TYPE_FORM_SELECT:
				$this->buf .= '<select>';
				foreach($data as $value) {
					$this->buf .= '<option>'.$value.'</option>';
				}
				$this->buf .= '</select>';
				break;

			default:
				throw new wf_exception(
					$this,
					WF_EXC_PRIVATE,
					"Unknown type"
				);
		}
	}

	public function render() {
		return($this->buf);
	}

}
