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

class core_file extends wf_agg {
	var $waf = NULL;
	var $loaded = array();
	var $cached = array();
	
	var $_core_cacher;
	
	/*
	 la zone determine un contexte par exemple img/css/js 
	*/
	public function loader($wf) {
		$this->wf = $wf;
		$struct = array(
			"create_time" => WF_INT,
			"zone" => WF_VARCHAR,
			"token" => WF_VARCHAR,
		);
		$this->wf->db->register_zone(
			"core_file", 
			"Core file table", 
			$struct
		);
		
		$this->_core_cacher = $this->wf->core_cacher();
		
		/* loading cache */
		if(($c = $this->_core_cacher->get("core_file")) != NULL)
			$this->cached = $c;
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * This function allow to create a link to an file 
	 * resource.
	 * This function will create the file if doesn't exists
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function linker($zone, $link) {
		/* create directly */
		$this->create_zoned($zone, $link);
		
		$rlink = $this->wf->linker("/$zone$link");
		
		return($rlink);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * This function is made to get file data
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function get_data($zone, $token) {
		/* get the filename */
		$filename = $this->get_filename($zone, $token);
		if(!$filename)
			return(NULL);
			
		return(file_get_contents($filename));
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * This function is used to output file to the stdout
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function echo_data($zone, $token) {
		/* get the filename */
		$filename = $this->get_filename($zone, $token);
		if(!$filename)
			return(NULL);
			
		readfile($filename);
		return(TRUE);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * This function is use to place data into the file
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function add_data($zone, $token) {
	
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Get the full path of a of token
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function get_filename($zone, $token) {
		$res = $this->get_zoned($zone, $token);
		if(!$res)
			return(NULL);
			
		$modrev = array_reverse($this->wf->modules);
		foreach($modrev as $mod => $mod_infos) {
			$tmp = $this->wf->modules[$mod][0].
				"/var/$zone$token";
				
			if(file_exists($tmp))
				return($tmp);
			
		}
		return(NULL);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Get last modification matrix
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function get_last_modified($zone, $token) {
		$filename = $this->get_filename($zone, $token);
		if(!$filename)
			return(NULL);
			
		$mtime = filemtime($filename);
	
		$ret = array(
			$mtime,
			date("D, d M Y H:i:s \G\M\T", $mtime)
		);

		return($ret);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Get file size
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function get_file_size($zone, $token) {
		$filename = $this->get_filename($zone, $token);
		if(!$filename)
			return(NULL);
			
		return(filesize($filename));
	}
	
	/* * * * * * * *  Private part * * * * * * * */
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Check if a zone + token are 'zoned'
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	private function get_zoned($zone, $_token) {
		/* regarde dans la liste deja chargé */
		if($this->loaded[$zone][$_token])
			return($this->loaded[$zone][$_token]);
		
		/* recuperation du cache exterrieur */
		if($this->cached[$zone][$_token]) {
			if(!$this->loaded[$zone])
				$this->loaded[$zone] = array();
			
			$this->loaded[$zone][$_token] = 
				$this->cached[$zone][$_token];
			return($this->loaded[$zone][$_token]);
		}
		
		$token = base64_encode($_token);
		
		/* questionne la base de donnée */
		$q = new core_db_select("core_file");
		$where = array(
			"zone" => $zone,
			"token" => $token
		);
		$q->where($where);
		$this->wf->db->query($q);
		$res = $q->get_result();

		/* Pas de résultat */
		if(!$res[0])
			return(FALSE);
		
		if(!$this->loaded[$zone])
			$this->loaded[$zone] = array();
		$this->loaded[$zone][$_token] = $res[0];
		
		if(!$this->cached[$zone])
			$this->cached[$zone] = array();
		$this->cached[$zone][$_token] = $res[0];
		
		/* store data into the cache */
		$this->_core_cacher->store("core_file", $this->cached);
		
		return($res[0]);
	}

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Create a new zoned zone & token
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	private function create_zoned($zone, $_token) {
		/* check if the zone exists */
		$res = $this->get_zoned($zone, $_token);
		if($res)
			return(TRUE);
		
		$token = base64_encode($_token);
		
		/* so create a new zone */
		$data = array(
			"create_time" => time(),
			"zone" => $zone,
			"token" => $token
		);

		/* store in database */
		$q = new core_db_insert("core_file", $data);
		$this->wf->db->query($q);
		
		if(!$this->loaded[$zone])
			$this->loaded[$zone] = array();
		$this->loaded[$zone][$_token] = $data;
		
		if(!$this->cached[$zone])
			$this->cached[$zone] = array();
		$this->cached[$zone][$_token] = $data;
	
		return(TRUE);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Delete a zone
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	private function delete_zoned($zone, $_token) {
		/* check if the zone exists */
		$res = $this->get_zoned($zone, $_token);
		if($res)
			return(TRUE);
			
		$token = base64_encode($_token);
		
		$q = new core_db_delete(
			"core_file", 
			array(
				"zone" => $zone,
				"token" => $token
			)
		);
		$this->wf->db->query($q);
		return(TRUE);
	}
}