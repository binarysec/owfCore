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

define("CORE_PROFILE_NUM",     900);
define("CORE_PROFILE_BOOL",    902);
define("CORE_PROFILE_VARCHAR", 903);
define("CORE_PROFILE_DATA",    904);
define("CORE_PROFILE_SELECT",  905);

class core_profile_context {
	public $wf;
	
	public $id;
	public $create_time;
	public $name;
	public $description;
	
	private $variables = array();
	private $need_up = FALSE;
	
	public function loader($wf) {
		$this->wf = $wf;

		$struct = array(
			"id" => WF_PRI,
			"create_time" => WF_INT,
			"name" => WF_VARCHAR,
			"description" => WF_VARCHAR
		);
		$this->wf->db->register_zone(
			"core_profile",
			"Profile",
			$struct
		);
		
		$struct = array(
			"id" => WF_PRI,
			"create_time" => WF_INT,
			"variable" => WF_VARCHAR,
			"description" => WF_VARCHAR,
			"profile_id" => WF_INT,
			"type" => WF_INT,
			"dft" => WF_DATA,
			"serial" => WF_DATA
		);
		$this->wf->db->register_zone(
			"core_profile_field",
			"Profile fields",
			$struct
		);

		$struct = array(
			"id" => WF_PRI,
			"variable" => WF_VARCHAR,
			"profile_id" => WF_INT,
			"session_id" => WF_INT,
			"value" => WF_DATA
		);
		$this->wf->db->register_zone(
			"core_profile_value",
			"Profile values",
			$struct
		);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * When destruction update the cache if necessary
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function __destruct() {
		if($this->need_up) {
			$this->need_up = FALSE;
			$this->wf->core_profile()->store_context(
				$this, $this->name
			);
		}
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Register a new variable 
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function register($var, $description, $type, $dft, $serial=NULL) {
		if($this->variables[$var])
			return($this->variables[$var]["value"]);
			
		$data = $this->db_find($var);
		if(!$data) {
			$insert = array(
				"create_time" => time(),
				"variable" => $var,
				"description" => base64_encode($description),
				"profile_id" => $this->id
			);

			switch($type) {
				case CORE_PROFILE_NUM:
					$insert["type"] = CORE_PROFILE_NUM;
					$insert["dft"] = $dft;
					break;
					
				case CORE_PROFILE_BOOL:
					$insert["type"] = CORE_PROFILE_BOOL;
					$insert["dft"] = $dft;
					break;
					
				case CORE_PROFILE_VARCHAR:
					$insert["type"] = CORE_PROFILE_VARCHAR;
					$insert["dft"] = $dft;
					break;
				
				case CORE_PROFILE_DATA:
					$insert["type"] = CORE_PROFILE_DATA;
					$insert["dft"] = $dft;
					break;
				
				default:
					throw new wf_exception(
						$this,
						WF_EXC_PRIVATE,
						"Preference type unknown for ".
						$this->name.
						"::$var"
					);
			}
		
			$q = new core_db_insert("core_profile_field", $insert);
			$this->wf->db->query($q);
			
			//$this->variables[$var] = $insert;
			
			/* need cacher update */
			$this->need_up = TRUE;
		}
		else if($description != $data["description"]) {
			$q = new core_db_update("core_profile_field");
			$where = array();
			$where["variable"] = $var;
			$where["profile_id"] = $this->id;
			
			$insert = array(
				"description" => base64_encode($description)
			);

			$q->where($where);
			$q->insert($insert);
			$this->wf->db->query($q);
			
// 			if(!is_array($this->variables[$var]))
// 				$this->variables[$var] = array();
// 				
// 			$this->variables[$var] = array_merge(
// 				&$data,
// 				&$insert
// 			);
// 
// 			/* need cacher update */
// 			$this->need_up = TRUE;
		}
		
		//return($this->variables[$var]["value"]);
		return(true);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Change the value of a variable 
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function set_value($var, $uid, $value) {
		$ret = $this->db_find($var, $uid);

		$where = array(
			"variable" => $var,
			"profile_id" => $this->id,
			"session_id" => $uid
		);

		/* update database */
		if($ret) {
			$q = new core_db_update("core_profile_value");
			$insert = array("value" => $value);
			$q->where($where);
			$q->insert($insert);
		}
		else {
			$q = new core_db_insert(
				"core_profile_value",
				array_merge($where, array("value" => $value))
			);
		}
		$this->wf->db->query($q);
	
		/* update short cache */
		//$this->variables[$var]["value"] = $value;
		
		/* need cacher update */
		$this->need_up = TRUE;
		
		return(TRUE);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Get a value
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function get_value($var, $uid) {
		$ret = $this->db_find($var, $uid);
		return($ret["value"]);
	}

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Get the default value
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function get_default($var, $uid) {
		$ret = $this->db_find($var, $uid);
		return($ret["dft"]);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Get/load all fields
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function get_all() {
		$q = new core_db_select("core_profile_field");
		$where = array("profile_id" => (int)$this->id);
		$q->where($where);
		$this->wf->db->query($q);
		$res = $q->get_result();

// 		foreach($res as $info)
// 			$this->variables[$info["variable"]] = $info;

		/* need cacher update */
// 		$this->need_up = TRUE;
		
// 		return($this->variables);

		$vars = array();
		foreach($res as $info) {
			$vars[$info["variable"]] = $info;
		}
		return($vars);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Low level function to find variable
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	private function db_find($var, $uid = NULL) {
// 		if(is_array($this->variables[$var]))
// 			return($this->variables[$var]);
			
		$q = new core_db_select("core_profile_value");
		$where = array();
		$where["variable"] = $var;
		$where["profile_id"] = $this->id;
		if($uid) $where["session_id"] = $uid;
		$q->where($where);
		$this->wf->db->query($q);
		$res = $q->get_result();
		
		/* store short cache */
// 		$this->variables[$var] = $res[0];
// 		
// 		/* need cacher update */
// 		$this->need_up = TRUE;

		return($res[0]);
	}
}

class core_profile extends wf_agg {
	private $_core_cacher;
	
	public function loader($wf) {
		$this->wf = $wf;

		$this->_core_cacher = $wf->core_cacher();
		
		$struct = array(
			"id" => WF_PRI,
			"create_time" => WF_INT,
			"name" => WF_VARCHAR,
			"description" => WF_VARCHAR
		);
		$this->wf->db->register_zone(
			"core_profile",
			"Profile",
			$struct
		);
		
		$struct = array(
			"id" => WF_PRI,
			"create_time" => WF_INT,
			"variable" => WF_VARCHAR,
			"description" => WF_VARCHAR,
			"profile_id" => WF_INT,
			"type" => WF_INT,
			"dft" => WF_DATA,
			"serial" => WF_DATA
		);
		$this->wf->db->register_zone(
			"core_profile_field",
			"Profile fields",
			$struct
		);

		$struct = array(
			"id" => WF_PRI,
			"variable" => WF_VARCHAR,
			"profile_id" => WF_INT,
			"session_id" => WF_INT,
			"value" => WF_DATA
		);
		$this->wf->db->register_zone(
			"core_profile_value",
			"Profile values",
			$struct
		);
	}
	
	private $contexts = array();
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Register a new profile and return the object
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function register_profile($name, $description=NULL, $lang_ctx=NULL) {
		$cvar = "core_profile_RG_$name";
		
		/* local and short cache */
		if(is_object($this->contexts[$name]))
			return($this->contexts[$name]);
			
		/* look at the long cache */
		$cache = $this->_core_cacher->get($cvar);
		if(is_object($cache)) {
			$cache->wf = $this->wf;
			$this->contexts[$name] = $cache;
			return($cache);
		}

		$data = $this->group_find($name);
		if(!$data) {
			$insert = array(
				"create_time" => time(),
				"name" => $name,
				"description" => base64_encode($description),
			);
		
			$q = new core_db_insert_id("core_profile", "id", $insert);
			$this->wf->db->query($q);
			$res = $q->get_result();
			$id = $res["id"];
			
			$this->contexts[$name] = new core_profile_context(
				$this->wf
			);
			
			$this->contexts[$name]->id = (int)$id;
			$this->contexts[$name]->create_time = 
				(int)$insert["create_time"];
			$this->contexts[$name]->name = $insert["name"];
			$this->contexts[$name]->description = base64_decode(
				$insert["description"]
			);
		}
		else {
			if($description) {
				$q = new core_db_update("core_profile");
				$where = array();
				$where["name"] = $name;
				$insert = array();
			
				$insert["description"] = base64_encode($description);
				$q->where($where);
				$q->insert($insert);
				$this->wf->db->query($q);
			}
			
			$id = $data[0]["id"];

			/* store into the short cache */
			$this->contexts[$name] = new core_profile_context(
				$this->wf
			);
			
			/* update object information */
			$this->contexts[$name]->id = (int)$id;
			$this->contexts[$name]->create_time = 
				(int)$data[0]["create_time"];
			$this->contexts[$name]->name = $data[0]["name"];
			
			$this->contexts[$name]->description = base64_decode(
				$insert["description"] ?
					$insert["description"] :
					$data[0]["description"]
			);
			
			
		}

		$this->store_context($this->contexts[$name], &$name);

		return($this->contexts[$name]);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Opaque function to store the cache context
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function store_context($obj, $name) {
		$cvar = "core_profile_RG_$name";
		
		/* store the objet into the cache */
		unset($obj->wf);
		$this->_core_cacher->store(
			$cvar,
			$obj
		);
		$obj->wf = $this->wf;
		
		return(TRUE);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Function used to find group
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function group_find($name=NULL, $id=NULL) {
		$q = new core_db_select("core_profile");
		$where = array();
	
		if($name)
			$where["name"] = $name;
		if($id)
			$where["id"] = $id;
			
		$q->where($where);
		$this->wf->db->query($q);
		$res = $q->get_result();

		return($res);
	}

}
