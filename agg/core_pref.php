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
	
	public function register_group($name, $description=NULL) {
		$cvar = "core_pref_RG_$name";
		
		/* look at the cache */
		$cache = $this->_core_cacher->get($cvar);
		if($cache)
			return($cache["id"]);
		
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
			
			$this->_core_cacher->store(
				$cvar,
				&$res
			);
			
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
			
			$this->_core_cacher->store(
				$cvar,
				&$data[0]
			);
		}

		return($id);
	}
	
	public function register($var, $description, $type, $dft, $group=0, $serial=NULL) {
		$cvar = "core_pref_R$group"."_$var";
		
// 		$cache = $this->_core_cacher->get($cvar);
// 		if($cache)
// 			return($cache["value"]);
		
		$data = $this->db_find($var);
		if(!$data) {

			$insert = array(
				"create_time" => time(),
				"variable" => $var,
				"description" => base64_encode($description),
				"group_id" => $group,
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
				
				default:
					/*! \todo exeption */
					exit(0);
			}
		
			$q = new core_db_insert("core_pref", $insert);
			$this->wf->db->query($q);
			
			$this->_core_cacher->store(
				$cvar,
				&$insert
			);
			
		}
		else {
			$q = new core_db_update("core_pref");
			$where = array();
			$where["variable"] = $var;
			$insert = array();
			$insert["description"] = base64_encode($description);
// 			$insert["dft"] = $dft;
			$insert["group_id"] = $group;
					
			$q->where($where);
			$q->insert($insert);
			$this->wf->db->query($q);
			
			if(!is_array($cache))
				$cache = array();
				
			$res = array_merge(
				$cache,
				$insert
			);
			
			$this->_core_cacher->store(
				$cvar,
				&$res
			);
		}

		return($this->cache[$var]["value"]);
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