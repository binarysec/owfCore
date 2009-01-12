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
 *  opération de 'reverse engineering'.                  *
 *                                                       *
 *  Warning : this software product is protected by      *
 *  copyright law and international copyright treaties   *
 *  as well as other intellectual property laws and      *
 *  treaties. Is is strictly forbidden to reverse        *
 *  engineer, decompile or disassemble this software     *
 *  product.                                             *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

class core_cacher_memcached extends core_cacher_lib {
	private $_core_pref;

	private $host        = 'localhost';
	private $port        = 11211;
	private $persistant  = true;
	private $compression = true;

	private $memcache = null;

	public function __construct($wf) {
		$this->wf = $wf;

		/* load conf */
		$conf = &$this->wf->ini_arr['memcache'];
		if($conf['host'])
			$this->host = $conf['host'];
		if($conf['port'])
			$this->port = $conf['port'];
		if($conf['persistant'])
			$this->persistant = $conf['persistant'];
		if($conf['compression'])
			$this->compression = $conf['compression'];

		/* connect to memcached */
		$this->memcache = new memcache;

		if($this->persistant) {
			$conn = @$this->memcache->pconnect(
				$this->host,
				$this->port
			);
		}
		else {
			$conn = @$this->memcache->connect(
				$this->host,
				$this->port
			);
		}

		if(!$conn) {
			throw new wf_exception(
				$this,
				WF_EXC_PRIVATE,
				'Cannot connect to memcached server ('.$this->host.':'.$this->port.').'
			);
		}
	}

	public function __destruct() {
		/* close non-persistant connection */
		if(!$this->persistant) {
			$this->memcache->close();
		}
	}
	
	public function store($var, $val, $timeout) {
		return($this->memcache->set($var, serialize($val), $this->compression, $timeout));
	}
	
	public function get($var) {
		/* auto deserialization */
 		return($this->memcache->get($var));
	}
	
	public function delete($var) {
		return($this->memcache->delete($var));
	}

	public function clear() {
		return($this->memcache->flush());
	}

	public function get_banner() {
		$status = $this->memcache->getServerStatus($this->host);
		return(
			'memcached '.$this->memcache->getVersion().' '.
			'('.(($status) ? 'online' : 'offline').')'
		);
	}
}
