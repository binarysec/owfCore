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
	var $wf;

	/**
	 * Constructor
	 *
	 * @param $wf The web_framework object
	 */
	abstract public function __construct($wf);

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
 * Cache grouping system
 */
class core_cacher_group {
	private $name;
	private $wf;
	private $ref = array();
	private $ref_update = FALSE;
	private $core_cacher;
	private $group;
	
	/**
	 * Constructor
	 * @param $wf Web Framework
	 * @param $name The group name
	 */
	public function __construct($wf, $name) {
		$this->wf = $wf;
		$this->name = $name;
		$this->core_cacher = $this->wf->core_cacher();
		$this->group = "core_cacher_group_".$this->name;

		/* load group */
		$this->ref = $this->core_cacher->get($this->group);
	}
	
	/**
	 * Destructor
	 * @param $wf Web Framework
	 * @param $name The group name
	 */
	public function __destruct() {
		if($this->ref_update) {
			$this->core_cacher->store(
				$this->group,
				$this->ref
			);
		}
	}
	
	/**
	 * Store a key-value pair
	 * @param $var The key
	 * @param $val The value
	 * @param $timeout Maximum time (in milliseconds)
	 */
	public function store($var, $val, $timeout=NULL) {
		$this->ref[$var] = TRUE;
		$this->ref_update = TRUE;
		return($this->core_cacher->store($this->group.'_'.$var, $val, $timeout));
	}

	/**
	 * Retrieve a cached value
	 *
	 * @param $var The key
	 * @return The cached value
	 */
	public function get($var) {
		return($this->core_cacher->get($this->group.'_'.$var));
	}

	/**
	 * Delete a cached value
	 * @param $var The key
	 */
	public function delete($var) {
		return($this->core_cacher->delete($this->group.'_'.$var));
	}

	/**
	 * Delete all cached values of this group
	 */
	public function clear() {
		foreach($this->ref as $key => $val) {
			$this->core_cacher->delete($this->group.'_'.$key);
		}
		$this->ref_update = TRUE;
		return(TRUE);
	}
	
}

 
/**
 * Cache system in shared memory
 */
class core_cacher extends wf_agg {

	private $system    = NULL; /**< The cache driver */
	private $namespace = NULL; /**< The cache namespace */

	/**
	 * Aggregator loader
	 *
	 * @param $wf The web_framework object
	 */
	public function loader($wf) {
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
		if($this->system)
			return($this->system->delete($this->namespace.$var));
		return(NULL);
	}

	/**
	 * Delete all cached values
	 */
	public function clear() {
		if($this->system)
			return($this->system->clear());
		return(NULL);
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
		return("None");
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
	
	
	private $group_obj = array();
	
	/**
	 * Create a new cache group
	 * @param $name The group name
	 */
	public function create_group($name) {
		if(is_object($this->group_obj[$name]))
			return($this->group_obj[$name]);
			
		$obj = new core_cacher_group($this->wf, $name);
		$this->group_obj[$name] = $obj;
		return($this->group_obj[$name]);
	}

}
