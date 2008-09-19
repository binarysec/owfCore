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

define("CORE_LOCKED",       1);
define("CORE_UNLOCKED",     2);
define("CORE_LOCK_TIMEOUT", 3);

define("CORE_LOCK_DIE_TIMEOUT", 5*60);
define("CORE_LOCK_DFT_TIMEOUT", 2*60);

abstract class core_lock_driver {
	abstract public function lock($name, $timeout);
	abstract public function unlock($name);
	abstract public function get_banner($name);
}

class core_lock extends wf_agg {
	
	var $driver;
	var $timeout_die;
	var $timeout_dft;
	
	public function loader($wf) {
		$this->wf = $wf;
		
		$this->driver = $this->wf->get_ini(
			"common", "lock_driver"
		);
		$this->timeout_die = $this->wf->get_ini(
			"common", "lock_timeout_die"
		);
		$this->timeout_dft = $this->wf->get_ini(
			"common", "lock_timeout_default"
		);
		

	}
	
	public function lock($name, $timeout=4) {
	
	}
	
	public function unlock($name) {
	
	}
	
}

?>