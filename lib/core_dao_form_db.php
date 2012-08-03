<?php


class core_dao_form_db {
	public $id;
	protected $wf;
	
	protected $a_dao;
	
	protected $capable;
	public $struct;
	public $name;
	public $description;
	public $aggregator;
	
	protected $db = array();
	public $data;
	
	public function __construct(
			$wf,
			$aggregator,
			$capable,
			$struct,
			$name,
			$description=NULL
		) {
		$this->capable = $capable;
		$this->aggregator = $aggregator;
		
		$this->wf = $wf;
		$this->a_dao = $this->wf->core_dao();
		$this->struct = $struct;
		$this->name = $name;
		$this->description = $description;
		$this->data = &$this->struct["data"];
		$this->cipher = $this->wf->core_cipher();
		
		/* create DB schemas */
		foreach($this->struct["data"] as $key => $val)
			$this->db[$key] = $val["type"];	
		$this->wf->db->register_zone(
			$this->name, 
			$this->description, 
			$this->db
		);
		$this->a_dao->register($this);
	
	}
	
	
	public function set_join($table, $colname) {
	
	}
	
	public function add($data) {
		$q = new core_db_insert($this->name, $data);
		$this->wf->db->query($q);
		$uid = $this->wf->db->get_last_insert_id($this->name.'_id_seq');
		return($uid);
	}
	
	public function remove($where=array()) {
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
		$q = new core_db_update($this->name);
		$q->where($where);
		$q->insert($data);
		$this->wf->db->query($q);
		return(TRUE);
	}
	
	public function get($where=NULL, $order=NULL, $limit=NULL, $offset=NULL) {
		$q = new core_db_select($this->name);
		if($where)
			$q->where($where);
		$limit = ($limit != NULL ? $limit : -1);
		$offset = ($offset != NULL ? $offset : -1);
		$q->limit($limit, $offset);
		$this->wf->db->query($q);
		$res = $q->get_result();
		
		return($res);
	}
	
	public function add_link($uid=null) {
		$back_url = $this->cipher->encode($_SERVER['REQUEST_URI']);
		$l = $this->wf->linker("/dao/".$this->aggregator)."?oid=".$this->id;
		if($uid)
			$l .= "&uid=".$uid;
			$l .= "&back=$back_url";
		return($l);
	}
	
	public function mod_link($uid) {
		$back_url = $this->cipher->encode($_SERVER['REQUEST_URI']);
		$l = $this->wf->linker("/dao/".$this->aggregator).
			"?oid=".$this->id.
			"&uid=".$uid.
			"&back=$back_url";
			
		return($l);
	}
	
	public function del_link($uid, $back_back=FALSE) {
		
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
