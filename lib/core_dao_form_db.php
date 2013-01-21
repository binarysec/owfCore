<?php

class core_dao_form_db {
	protected $wf;
	protected $a_dao;
	protected $cipher;
	
	/* name of the aggregator */
	public $aggregator;
	
	/* capabilities of the dao (add, remove, ..) */
	public $capable;
	
	/* structure */
	public $struct;
	
	/* table informations */
	public $name;
	public $description;
	
	public $id;
	public $data;
	protected $db = array();
	
	public function __construct(
			$wf,
			$aggregator,
			$capable,
			$struct,
			$name,
			$description = ""
		) {
		/* fill all vars */
		$this->wf = $wf;
		$this->a_dao = $this->wf->core_dao();
		$this->cipher = $this->wf->core_cipher();
		$this->aggregator = $aggregator;
		$this->capable = $capable;
		$this->struct = $struct;
		$this->name = $name;
		$this->description = $description;
		$this->data = &$this->struct["data"];
		foreach($this->struct["data"] as $key => $val) {
			if(isset($val["kind"], $val["dao"], $val["field-id"]) && $val["kind"] == OWF_DAO_LINK_MANY_TO_ONE) {
				if(is_array($val["dao"]) && isset($val["type"])) {
					$this->db[$key] = $val["type"];
				}
				else {
					$this->db[$key] = $val["dao"]->struct["data"][$val["field-id"]]["type"] & ~WF_PRIMARY & ~WF_AUTOINC;
				}
			}
			elseif(isset($val["kind"])) {
				if($val["kind"] == OWF_DAO_MAP) {
					$this->db[$key."_latitude"] = WF_FLOAT;
					$this->db[$key."_longitude"] = WF_FLOAT;
				}
				elseif($val["kind"] == OWF_DAO_LINK_MANY_TO_MANY) {
					/* register link table */
					$this->wf->db->register_zone(
						$val["link"]["table"],
						$val["link"]["table"],
						array(
							$val["link"]["primary"] => WF_INT,
							$val["link"]["secondary"] => WF_INT,
						)
					);
				}
				else {
					$this->db[$key] = $val["type"];
				}
				
				/* for octopus kind, add other tables */
				if($val["kind"] == OWF_DAO_OCTOPUS && isset($val["affix"])) {
					foreach($this->wf->modules as $module) {
						$lib_dir = "$module[0]/lib/";
						$afx_length = strlen($val["affix"]);
						if(file_exists($lib_dir)) {
							foreach(scandir($lib_dir) as $file) {
								if(	$file[0] != "." &&
									strlen($file) - $afx_length - 4 > 0 &&
									substr($file, 0, $afx_length) == $val["affix"]
									) {
										$obj_name = substr($file, 0, strlen($file) - 4);
										$obj = new ${"obj_name"}($this->wf);
										
										//if(!is_subclass_of($obj, "core_dao_octopus", false)) PHP 5.3.9 only
										if(!is_subclass_of($obj, "core_dao_octopus"))
											throw new wf_exception(
												$this,
												WF_EXC_PUBLIC,
												"Class ".get_class($obj)." does not inherit core_dao_octopus"
											);
										
										$obj->father = $this;
										
										if(isset($this->childs[$obj->get_id()]))
											throw new wf_exception(
												$this,
												WF_EXC_PUBLIC,
												"You used two same ids for an OWF_DAO_OCTOPUS"
											);
										
										$this->childs[$obj->get_id()] = $obj;
										
										$fields = array();
										foreach($obj->get_struct() as $field => $info)
											$fields[$field] = $info["db"];
										
										$key = isset($val["db-field"]) ?
											$val["db-field"] : "father_id";
										
										$struct = array_merge(
											array($key => WF_INT | WF_PRIMARY),
											$fields
										);
										
										$this->wf->db->register_zone(
											$name."_".$obj->get_name(),
											$obj->get_description(),
											$struct
										);
								}
							}
						}
					}
				}
			}
			else {
				$this->db[$key] = $val["type"];
			}
		}
		
		/* register zone */
		$this->wf->db->register_zone(
			$this->name,
			$this->description, 
			$this->db
		);
		
		/* register dao */
		$this->a_dao->register($this);
		
		/* cache */
		$this->gcache = $this->wf->core_cacher()->create_group(
			$this->name."_gcache"
		);
	}
	
	public function set_join($table, $colname) {
	
	}
	
	public function add($data) {
		$q = new core_db_insert($this->name, $data);
		$this->wf->db->query($q);
		return $this->wf->db->get_last_insert_id($this->name.'_id_seq');
	}
	
	public function remove($where = array()) {
		$q = new core_db_delete(
			$this->name,
			$where
		);
		$this->wf->db->query($q);
		return(TRUE);
	}
	
	public function modify($where, $data) {
		if(!$data)
			return(TRUE);
		$q = new core_db_update($this->name, $data, $where);
		$this->wf->db->query($q);
		return(TRUE);
	}
	
	public function get($where=NULL, $order=NULL, $limit = -1, $offset = -1) {
		$cache_line = $this->name."_get";
		if(is_array($where))
			foreach($where as $k => $v)
				$cache_line .= "_$k:$v";
		
		if(($cache = $this->gcache->get($cache_line)))
			return($cache);
		
		$q = new core_db_select($this->name, null, $where);
		$q->limit($limit, $offset);
		if(!is_null($order))
			$q->order($order);
		$this->wf->db->query($q);
		$res = $q->get_result();
		
		$this->gcache->store($cache_line, $res);
		
		return $res;
	}
	
	public function add_link($uid=null) {
		/*if(($this->capable & OWF_DAO_ADD) != OWF_DAO_ADD)
			return "";*/
		
		$back_url = $this->cipher->encode($_SERVER['REQUEST_URI']);
		$l = $this->wf->linker("/dao/".$this->aggregator).
			"?oid=".$this->id."&back=$back_url";
		if($uid)
			$l .= "&uid=".$uid;
		return($l);
	}
	
	public function mod_link($uid, $route_only = false) {
		/*if(($this->capable & OWF_DAO_EDIT) != OWF_DAO_EDIT)
			return "";*/
		
		$route = "/dao/".$this->aggregator;
		$back_url = $this->cipher->encode($_SERVER['REQUEST_URI']);
		
		return $route_only ? $route :
			$this->wf->linker($route)."?oid=".$this->id.
			"&uid=$uid&back=$back_url";
	}
	
	public function del_link($uid, $back_back=FALSE) {
		/*if(($this->capable & OWF_DAO_REMOVE) != OWF_DAO_REMOVE)
			return "";*/
		
		/*If del link is in a modification page, back to a list view*/
		if($back_back)
			$back_url = $this->wf->get_var('back');
		else
			$back_url = $this->cipher->encode($_SERVER['REQUEST_URI']);
			
		$l = $this->wf->linker("/dao/".$this->aggregator).
			"?oid=".$this->id.
			"&uid=".$uid.
			"&back=$back_url".
			"&action=del";
			
		return($l);
	}
	
}
