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

/**
 * Driver for cache in shared memory
 */
abstract class core_cacher_lib {

	// Attributes

	var $wf; /**< The web_framework object */


	// Construction / Destruction

	/**
	 * Constructor
	 *
	 * @param $wf The web_framework object
	 */
	abstract public function __construct($wf);


	// Abstract methods

	/**
	 * Store a key-value pair
	 *
	 * @param $var The key
	 * @param $val The value
	 * @param $timeout Maximum time (in milliseconds) to keep the value cached
	 */
	abstract public function store($var, $val, $timeout);

	/**
	 * Retrieve a cached value
	 *
	 * @param $var The key
	 *
	 * @return The cached value
	 */
	abstract public function get($var);

	/**
	 * Delete a cached value
	 *
	 * @param $var The key
	 *
	 */
	abstract public function delete($var);

	/**
	 * Delete all cached values
	 */
	abstract public function clear();

	/**
	 * Get the cache driver banner
	 */
	abstract public function get_banner();

}

/**
 * Cache system in shared memory
 */
class core_cacher extends wf_agg {

	// Attributes

	private $system    = NULL; /**< The cache driver */
	private $namespace = NULL; /**< The cache namespace */


	// Methods

	/**
	 * Aggregator loader
	 *
	 * @param $wf The web_framework object
	 */
	public function loader($wf, $position) {
		$this->wf = $wf;

		/* use the framework instance name as namespace */
		$this->namespace = $this->wf->modkey;

		/* enable the cache if possible */
		$this->enable();
	}

	/**
	 * Store a key-value pair
	 *
	 * @param $var The key
	 * @param $val The value
	 * @param $timeout Maximum time (in milliseconds) to keep the value cached
	 */
	public function store($var, $val, $timeout=NULL) {
		if (is_null($timeout))
			$timeout = $this->wf->ini_arr["common"]["max_cache_timeout"];

		if($this->system)
			$this->system->store($this->namespace.$var, $val, $timeout);
		return(TRUE);
	}

	/**
	 * Retrieve a cached value
	 *
	 * @param $var The key
	 *
	 * @return The cached value
	 */
	public function get($var) {
		if($this->system)
			return($this->system->get($this->namespace.$var));
		return(NULL);
	}

	/**
	 * Delete a cached value
	 *
	 * @param $var The key
	 *
	 */
	public function delete($var) {
		return($this->system->delete($this->namespace.$var));
	}

	/**
	 * Delete all cached values
	 */
	public function clear() {
		if($this->system)
			return($this->system->clear());
	}

	/**
	 * Is the shared memory cache system enabled ?
	 *
	 * @return One of the following value
	 * @retval true if enabled
	 * @retval false if disabled
	 */
	public function is_enabled() {
		return(!!($this->system));
	}

	/**
	 * Get the cache driver banner
	 */
	public function get_banner() {
		if($this->system)
			return($this->system->get_banner());
		return(NULL);
	}

	/**
	 * Disable the cache system
	 */
	public function disable() {
		$this->system = NULL;
	}

	/**
	 * Enable the cache system
	 */
	public function enable() {
		/* check if APC is available */
		if(function_exists("apc_sma_info")) {
			$this->system = new core_cacher_apc(&$wf);
		}
		/* check if eAccelerator is available
		   eAccelerator can have been compiled without the shared memory
		   support (by default for security purpose), so we test
		   eaccelerator_get */
		else if(function_exists("eaccelerator_get")) {
			$this->system = new core_cacher_eaccelerator(&$wf);
		}
		/* no cache system driver */
		else {
			$this->system = NULL;
		}
	}

}
