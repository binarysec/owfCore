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

class core_datasource_db extends core_datasource {

	public function get_struct() {
		$dbfields = $this->wf->db->get_zone($this->get_name());
		$struct = array();
		foreach($dbfields as $dbfield) {
			$struct[$dbfield['b.name']] = $dbfield['b.type'];
		}
		return($struct);
	}

	public function get_data($conds = array(), $order = array(), $offset = null, $nb = null) {
		$q = new core_db_adv_select();
		$q->alias('t', $this->get_name());
		foreach($this->preconds as $cond) {
			$q->do_comp($cond[0], $cond[1], $cond[2]);
			$q->do_and();
		}
		foreach($conds as $cond) {
			$q->do_comp($cond[0], $cond[1], $cond[2]);
			$q->do_and();
		}
		if($order) {
			$q->order($order);
		}
		if(!is_null($offset) && $nb) {
			$q->limit($nb, $offset);
		}
		$this->wf->db->query($q);
		return($q->get_result());
	}

	public function get_options($field) {
		$q = new core_db_select_distinct($this->get_name(), array($field));
		$q->order(array($field => WF_ASC));
		$this->wf->db->query($q);
		return($q->get_result());
	}

	public function get_num_rows($conds) {
		$q = new core_db_adv_select($this->get_name(), array($field));
		$q->request_function(WF_REQ_FCT_COUNT);
		$q->alias('t', $this->get_name());
		foreach($this->preconds as $cond) {
			$q->do_comp($cond[0], $cond[1], $cond[2]);
		}
		foreach($conds as $cond) {
			$q->do_comp($cond[0], $cond[1], $cond[2]);
		}
		$this->wf->db->query($q);
		$res = $q->get_result();
		return($res[0]['COUNT(*)']);
	}

}
