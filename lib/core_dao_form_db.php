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
		$this->capable = $capable;
		$this->struct = $struct;
		$this->name = $name;
		$this->description = $description;
		$this->data = &$this->struct["data"];
		
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
	
	public function add($data) {
		$q = new core_db_insert($this->name, $data);
		$this->wf->db->query($q);
		$uid = $this->wf->db->get_last_insert_id($this->name.'_id_seq');
		return(TRUE);
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
	
	public function get($where=NULL, $order=NULL) {
		$q = new core_db_select($this->name);
		if($where)
			$q->where($where);
		$this->wf->db->query($q);
		$res = $q->get_result();
		
		return($res);
	}

	public function get_dialog() {
		$class = "dao-dialog-".$this->aggregator.$this->id;
		$name = "dao-id-dialog-".rand();
		return(
			'<div id="'.$name.'" class="'.$class.' dao-dialog">'.
			'</div>'
		);
	}
	
	public function button_add($text) {
		$class = "dao-button-add-".$this->aggregator.$this->id;
		$name = "dao-id-add-".rand();
		$html = '<span id="'.$name.'" '.
			' data-agg="'.$this->aggregator.'"'.
			' data-aggid="'.$this->id.'"'.
		
			'class="'.$class.' dao-button-add">'.
			$text.
			'</span>';
		return($html);
		
	}
	
	public function button_remove($text, $id) {
		$class = "dao-button-rm-".$this->aggregator.$this->id;
		$name = "dao-id-rm-".rand();
		$html = '<span id="'.$name.'" '.
			' data-agg="'.$this->aggregator.'"'.
			' data-aggid="'.$this->id.'"'.
			' data-id="'.$id.'"'.
			' class="'.$class.' dao-button-del">'.
			$text.
			'</span>';
		return($html);
		
	}
	
	public function button_modify($text, $id) {
		$class = "dao-button-mod-".$this->aggregator.$this->id;
		$name = "dao-id-md-".rand();
		$html = '<span id="'.$name.'" '.
			
			' data-agg="'.$this->aggregator.'"'.
			' data-aggid="'.$this->id.'"'.
			' data-id="'.$id.'"'.
			
			' class="'.$class.' dao-button-mod">'.
			$text.
			'</span>';
		return($html);
	}
	
	
	

}
