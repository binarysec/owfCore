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

class core_lock_db extends core_lock_driver {
	private $_core_lock;
	
	public function __construct($wf) {
		$this->wf = $wf;
		$this->_core_lock = $wf->core_lock();
		
		$struct = array(
			"name" => WF_VARCHAR,
			"who" => WF_VARCHAR,
			"state" => WF_INT
		);
		$this->wf->db->register_zone(
			"core_lock_db", 
			"Core lock device using database", 
			$struct
		);
		
	}

	public function lock($name, $timeout) {
		echo"lala";
	}
	
	public function unlock($name) {
	
	}
	
	public function trylock($name) {
	
// 		$where = array(
// 			"name" => $name,
// 			"who" => $this->_core_lock->get_ident()
// 		);
// 		$update = array(
// 			"who" => $this->_core_lock->get_ident()
// 			"state" => CORE_LOCKED
// 		);
		
		
	}
	
	private function push() {
	
// 		$q = new core_db_insert("core_session", $insert);
// 		$this->wf->db->query($q);
		
	}
	
	private function select() {
	
	}
	
	public function get_banner() {
		return("Database");
	}
	
	
}

?>