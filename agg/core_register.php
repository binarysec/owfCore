<?php

define("CORE_REGISTER_GLOBAL", 0);
define("CORE_REGISTER_USER",   1);

class core_register extends wf_agg {
	private $data_cache = array();
	
	public function loader($wf) {
		$this->wf = $wf;
		
		$struct = array(
			"id" => WF_PRI,
			"variable" => WF_VARCHAR,
			"value" => WF_DATA,
			"obj_type" => WF_INT,
			"obj_id" => WF_INT
		);
		$this->wf->db->register_zone(
			"core_register", 
			"Core register table", 
			$struct
		);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * 
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function set_global_data() {
	
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * 
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function unset_global_data() {
	
	
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * 
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function get_global_data() {
	
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Add user specific information
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function set_user_data($data) {
// 		var_dump($data);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Unset a list of key
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function unset_user_data() {
	
	
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Get a user spec data
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function get_user_data() {
	
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * 
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	private function get($conds, $extra=NULL) {
		if($extra && is_string($conds)) {
			
			$where = array($conds => $extra);
			var_dump($where);
		}
		else {
			$where = &$conds;
		}
		
		$q = new core_db_select("core_register");
		$q->where($where);
		$this->wf->db->query($q);
		$res = $q->get_result();
		return($res);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * 
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	private function modify($data, $where) {
		if(!$data)
			return(TRUE);
// CORE_REGISTER_GLOBAL
		$q = new core_db_update("core_register");
		$q->where($where);
		$q->insert($data);
		$this->wf->db->query($q);
		return(TRUE);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Private function to load on page cache user data
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	private function load_data($obj, $obj_id=NULL) {
	
		if($obj_id) {
			$res = $this->get(array(
				"obj_type" => $obj,
				"obj_id" => $obj_id,
			));
			$cid = "/i/$obj/$obj_id:";
		}
		else {
			$res = $this->get("obj_type", $obj);
			$cid = "/t/$obj";
		}
		
		
// 		if(!$this->data_cache[$cid]) {
// 			$res = $this->user->get("id", $uid);
// 			$this->data_cache[$cid] = @unserialize($res[0]["session_data"]);
// 		}
// 		return($this->data_cache[$uid]);
	}
	
}
