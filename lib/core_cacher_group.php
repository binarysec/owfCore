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
		$this->group = "ccg_".$this->name;

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
		if(is_array($this->ref)) {
			foreach($this->ref as $key => $val) {
				$this->core_cacher->delete($this->group.'_'.$key);
			}
			$this->ref_update = TRUE;
		}
		return(TRUE);
	}
	
}
