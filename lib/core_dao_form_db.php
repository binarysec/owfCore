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
					if(is_array($val["dao"]) && isset($val["type"]))
						$this->db[$key] = $val["type"];
					else
						$this->db[$key] = $val["dao"]->struct["data"][$val["field-id"]]["type"] & ~WF_PRIMARY & ~WF_AUTOINC;
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
				else
					$this->db[$key] = $val["type"];
			}
			else
				$this->db[$key] = $val["type"];
		}
		
		/* register zone */
		$this->wf->db->register_zone(
			$this->name,
			$this->description, 
			$this->db
		);
		
		/* register dao */
		$this->a_dao->register($this);
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
		$q = new core_db_select($this->name, null, $where);
		$q->limit($limit, $offset);
		if(!is_null($order))
			$q->order($order);
		$this->wf->db->query($q);
		return $q->get_result();
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
		/*if(($this->capable & OWF_DAO_ADD) != OWF_DAO_ADD)
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
