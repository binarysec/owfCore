<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * OpenWF - Open source Web Framework                    *
 * BinarySEC (c) (2000-2009) / www.binarysec.com         *
 * Author: Michael Vergoz <mv@binarysec.com>             *
 * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~         *
 *  Avertissement : ce logiciel est protégé par la       *
 *  loi du copyright et par les traités internationaux.  *
 *  Toute personne ne respectant pas ces dispositions    *
 *  se rendra coupable du délit de contrefaçon et sera   *
 *  passible des sanctions pénales prévues par la loi.   *
 *  Il est notamment strictement interdit de décompiler, *
 *  désassembler ce logiciel ou de procèder à des        *
 *  opération de 'reverse engineering'.                  *
 *                                                       *
 *  Warning : this software product is protected by      *
 *  copyright law and international copyright treaties   *
 *  as well as other intellectual property laws and      *
 *  treaties. Is is strictly forbidden to reverse        *
 *  engineer, decompile or disassemble this software     *
 *  product.                                             *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

abstract class core_datasource {

	protected $wf   = null;
	protected $name = null;
	public  $preconds = array();
	
	public function __construct($wf, $name) {
		$this->wf   = $wf;
		$this->name = $name;
	}

	public function get_name() {
		return($this->name);
	}

	/* get data struct */
	abstract public function get_struct();

	/* get data */
	abstract public function get_data($conds = array(), $order = array(), $offset = null, $nb = null);

	/* get options for a field (field must be an array) */
	abstract public function get_options($field);

	/* get total number of rows */
	abstract public function get_num_rows($conds);

	/* add a data precondition */
	public function add_preconds($in) {
		foreach($in as $v)
			$this->preconds[] = $v;
	}
}
