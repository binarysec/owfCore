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

class core_datasource {

	private $wf     = null;
	private $struct = array();
	private $data   = array();

	public function __construct($wf) {
		$this->wf = $wf;
	}

	/* set a direct data source */
	public function set_direct_source($struct, $data) {
		$this->struct = $struct;
		$this->rdata  = $data;
		$this->data   = $data;
	}

	/* apply conditions to data source */
	public function apply_conds($conds) {
		$this->data = array();
		if($conds) {
			foreach($this->rdata as $i => $row) {
				$satisfy = true;
				foreach($conds as $cond) {
					$col = &$cond[0];
					$op  = &$cond[1];
					$val = &$cond[2];
	
					if($op == '==' && $row[$col] != $val
					|| $op == '>=' && $row[$col] < $val
					|| $op == '<=' && $row[$col] > $val) {
						$satisfy = false;
					}
				}

				if($satisfy) {
					$this->data[] = $row;
				}
			}
		}
		else {
			$this->data = $this->rdata;
		}
	}

	/* get data struct */
	public function get_struct() {
		return($this->struct);
	}

	/* get data without applying conditions */
	public function get_rdata() {
		return($this->rdata);
	}

	/* get data with applying conditions */
	public function get_data() {
		return($this->data);
	}

}
