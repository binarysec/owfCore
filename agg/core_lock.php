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

define("CORE_LOCK_TIMEOUT", 60);

abstract class core_lock_driver {
	abstract public function lock($name, $timeout);
	abstract public function unlock($name);
	abstract public function trylock($name);
	abstract public function get_banner();
}

class core_lock extends wf_agg {
	
	private $driver;
	private $timeout;
	
	private $device;
	
	private $ident;
	
	public function loader($wf) {
		$this->wf = $wf;
		
		$this->driver = $this->wf->get_ini(
			"common", "lock_driver"
		);
		$this->timeout = $this->wf->get_ini(
			"common", "lock_timeout"
		);
		
		if(!$this->driver)
			$this->driver = "db";
		if(!$this->timeout)
			$this->timeout = CORE_LOCK_TIMEOUT;
			
		$this->ident = rand();
		
		$this->load_device();
	}
	
	public function lock($name, $timeout=NULL) {
		if($timeout)
			return($this->device->lock($name, $timeout));
		return($this->device->lock($name, $this->timeout));
	}
	
	public function unlock($name) {
		return($this->device->unlock($name));
	}
	
	public function trylock() {
		return($this->device->trylock($name));
	}
	
	public function get_ident() {
		return($this->ident);
	}
	
	private function load_device() {
		/* set the object name */
		$name = "core_lock_".$this->driver;
		
		/* load the device */
		$this->device = new ${name}($this->wf);
	}

}

?>