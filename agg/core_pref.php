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

define("CORE_PREF_NUM",      900);
define("CORE_PREF_BOOL",     902);
define("CORE_PREF_VARCHAR",  903);
define("CORE_PREF_DATA",     904);

class core_pref extends wf_agg {
	
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
	
	public function register_group($name, $description=NULL) {
		$data = $this->group_find($name);
		if(!$data) {

			$insert = array(
				"create_time" => time(),
				"name" => $name,
				"description" => base64_encode($description),
			);
		
			$q = new core_db_insert_id("core_pref_group", "id", $insert);
			$this->wf->db->query($q);
			$id = $q->get_result();
			$id = $id["id"];
		}
		else {
			$q = new core_db_update("core_pref_group");
			$where = array();
			$where["name"] = $name;
			$insert = array();
			if($description)
				$insert["description"] = base64_encode($description);
			$q->where($where);
			$q->insert($insert);
			$this->wf->db->query($q);

			$id = $data[0]["id"];
		}

		
		return($id);
	}
	
	
	public function set_value($var, $value) {
		$ret = $this->db_find($var);
		
		$q = new core_db_update("core_pref");
		$where = array();
		$where["variable"] = $var;
		$insert = array();
		$insert["value"] = $value;
		$q->where($where);
		$q->insert($insert);
		$this->wf->db->query($q);
		
		return(0);
	}
	
	public function get_value($var) {
		$ret = $this->db_find($var);
		return($ret["value"]);
	}

	public function get_default($var) {

	}
	
	public function get_all($id) {
		$q = new core_db_select("core_pref");
		$where = array();
		$where["group_id"] = intval($id);
		$q->where($where);
		$this->wf->db->query($q);
		$res = $q->get_result();
		return($res);
		
	}
	
	public function db_find($var) {
		$q = new core_db_select("core_pref");
		$where = array();
		$where["variable"] = $var;
		$q->where($where);
		$this->wf->db->query($q);
		$res = $q->get_result();
		$this->cache[$var] = $res[0];
		return($res[0]);
	}
	
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