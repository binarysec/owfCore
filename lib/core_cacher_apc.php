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

class core_cacher_apc extends core_cacher_lib {
	public function __construct($wf) {
		$this->wf = $wf;
	}
	
	public function store($var, $val, $timeout) {
		apc_store($var, serialize($val), $timeout);
		return(TRUE);
	}
	
	public function get($var) {
		$ret = apc_fetch($var);
		if($ret)
			return(unserialize($ret));
		return(NULL);
	}
	
	public function delete($var) {
		apc_delete($var);
		return(TRUE);
	}

	public function clear() {
		return apc_clear_cache("user");
	}

	public function get_banner() {
		$i = apc_cache_info();
		
		$d = "slots=$i[num_slots] ".
			"hits=$i[num_hits] ".
			"opfile=".count($i["cache_list"]);
		
		return("APC ($d)");
	}
}

?>