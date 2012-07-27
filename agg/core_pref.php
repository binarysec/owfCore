<?php

define("CORE_PREF_NUM",      900);
define("CORE_PREF_BOOL",     902);
define("CORE_PREF_VARCHAR",  903);
define("CORE_PREF_DATA",     904);
// define("CORE_PREF_SELECT",   905);

define("CORE_PREF_HIDDEN",   1000);

class core_pref_context {
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
			"core_pref_group", 
			"Group of global core preference table", 
			$struct
		);
		
		$struct = array(
			"id" => WF_PRI,
			"create_time" => WF_INT,
			"variable" => WF_VARCHAR,
			"description" => WF_VARCHAR,
			"group_id" => WF_INT,
			"type" => WF_INT,
			"dft" => WF_DATA,
			"value" => WF_DATA,
			"serial" => WF_DATA
		);
		$this->wf->db->register_zone(
			"core_pref", 
			"Core preference table", 
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
			$this->wf->core_pref()->store_context(
				$this, $this->name
			);
		}
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Register a new variable 
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function register($var, $description, $type, $dft, $serial=NULL) {
		if(array_key_exists($var,$this->variables))
			return($this->variables[$var]["value"]);
			
		$data = $this->db_find($var);
		if(!$data) {
			$insert = array(
				"create_time" => time(),
				"variable" => $var,
				"description" => base64_encode($description),
				"group_id" => $this->id
			);

			switch($type) {
				case CORE_PREF_NUM:
					$insert["type"] = CORE_PREF_NUM;
					$insert["dft"] = $dft;
					$insert["value"] = $dft;
					break;
					
				case CORE_PREF_BOOL:
					$insert["type"] = CORE_PREF_BOOL;
					$insert["dft"] = $dft;
					$insert["value"] = $dft;
					break;
					
				case CORE_PREF_VARCHAR:
					$insert["type"] = CORE_PREF_VARCHAR;
					$insert["dft"] = $dft;
					$insert["value"] = $dft;
					break;
				
				case CORE_PREF_DATA:
					$insert["type"] = CORE_PREF_DATA;
					$insert["dft"] = $dft;
					$insert["value"] = $dft;
					break;
				
				case CORE_PREF_HIDDEN:
					$insert["type"] = CORE_PREF_HIDDEN;
					$insert["dft"] = $dft;
					$insert["value"] = $dft;
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
		
			$q = new core_db_insert("core_pref", $insert);
			$this->wf->db->query($q);
			
			$this->variables[$var] = $insert;
			
			/* need cacher update */
			$this->need_up = TRUE;
		}
		else if($description != $data["description"]) {
			$q = new core_db_update("core_pref");
			$where = array();
			$where["variable"] = $var;
			$where["group_id"] = $this->id;
			
			$insert = array(
				"description" => base64_encode($description)
			);

			$q->where($where);
			$q->insert($insert);
			$this->wf->db->query($q);
			
			if(!is_array($this->variables[$var]))
				$this->variables[$var] = array();
				
			$this->variables[$var] = array_merge(
				&$data,
				&$insert
			);

			/* need cacher update */
			$this->need_up = TRUE;
		}
		
		return($this->variables[$var]["value"]);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Change the value of a variable 
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function set_value($var, $value) {
		$ret = $this->db_find($var);
		
		/* update database */
		$q = new core_db_update("core_pref");
		$where = array(
			"variable" => $var,
			"group_id" => $this->id
		);
		
		$insert = array("value" => $value);
		
		$q->where($where);
		$q->insert($insert);
		$this->wf->db->query($q);
		
		/* update short cache */
		$this->variables[$var]["value"] = $value;
		
		/* need cacher update */
		$this->need_up = TRUE;
		
		return(TRUE);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Get a value
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function get_value($var) {
		$ret = $this->db_find($var);
		return($ret["value"]);
	}

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Get the default value
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function get_default($var) {
		$ret = $this->db_find($var);
		return($ret["dft"]);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Get/load all variable
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function get_all() {
		$q = new core_db_select("core_pref");
		$where = array("group_id" => (int)$this->id);
		$q->where($where);
		$this->wf->db->query($q);
		$res = $q->get_result();

		foreach($res as $info)
			$this->variables[$info["variable"]] = $info;

		/* need cacher update */
		$this->need_up = TRUE;
		
		return($this->variables);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Low level function to find variable
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	private function db_find($var) {
		if(array_key_exists($var,$this->variables) && is_array($this->variables[$var]))
			return($this->variables[$var]);
			
		$q = new core_db_select("core_pref");
		$where = array();
		$where["variable"] = $var;
		$where["group_id"] = $this->id;
		$q->where($where);
		$this->wf->db->query($q);
		$res = $q->get_result();
		
		/* store short cache */
		$this->variables[$var] = isset($res[0]) ? $res[0] : NULL;
		
		/* need cacher update */
		$this->need_up = TRUE;

		return(isset($res[0]) ? $res[0] : NULL);
	}
}

class core_pref extends wf_agg {
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
			"core_pref_group", 
			"Group of global core preference table", 
			$struct
		);
		
		$struct = array(
			"id" => WF_PRI,
			"create_time" => WF_INT,
			"variable" => WF_VARCHAR,
			"description" => WF_VARCHAR,
			"group_id" => WF_INT,
			"type" => WF_INT,
			"dft" => WF_DATA,
			"value" => WF_DATA,
			"serial" => WF_DATA
		);
		$this->wf->db->register_zone(
			"core_pref", 
			"Core preference table", 
			$struct
		);
	}
	
	private $contexts = array();
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Register a new group of vars and return the object
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function register_group($name, $description=NULL) {
		$cvar = "core_pref_RG_$name";
		
		/* local and short cache */
		if(array_key_exists($name,$this->contexts) && is_object($this->contexts[$name]))
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
		
			$q = new core_db_insert_id("core_pref_group", "id", $insert);
			$this->wf->db->query($q);
			$res = $q->get_result();
			$id = $res["id"];
			
			$this->contexts[$name] = new core_pref_context(
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
				$q = new core_db_update("core_pref_group");
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
			$this->contexts[$name] = new core_pref_context(
				$this->wf
			);
			
			/* update object information */
			$this->contexts[$name]->id = (int)$id;
			$this->contexts[$name]->create_time = 
				(int)$data[0]["create_time"];
			$this->contexts[$name]->name = $data[0]["name"];
			
			$this->contexts[$name]->description = base64_decode(
				isset($insert["description"]) ?
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
		$cvar = "core_pref_RG_$name";
		
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
		$q = new core_db_select("core_pref_group");
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

?>
