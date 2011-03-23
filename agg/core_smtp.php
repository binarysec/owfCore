<?php


class core_smtp extends wf_agg {
	public $wf;
	public $struct;
	public $dao;
	public $lang;
	
	public function loader($wf) {
		$this->wf = $wf;
		$this->wf->core_dao();
		
		$this->lang = $this->wf->core_lang()->get_context("tpl/core/smtp/list");
		
		$this->struct = array(
			"form" => array(
				"perm" => array("core:smtp"),
				
			),
			"data" => array(
				"id" => array(
					"type" => WF_PRI,
					"perm" => array("core:smtp"),
				),
				"description" => array(
					"name" => $this->lang->ts("Server description"),
					"kind" => OWF_DAO_INPUT,
					"perm" => array("core:smtp"),
					"filter_cb" => array($this, "check_description"),
					"type" => WF_VARCHAR
				),
				"server_ip" => array(
					"size" => 22,
					"name" => $this->lang->ts("Server IP or Hostname"),
					"kind" => OWF_DAO_INPUT,
					"perm" => array("core:smtp"),
					
					"type" => WF_VARCHAR
				),
				"server_port" => array(
					"size" => 10,
					"value" => 25,
					"name" => $this->lang->ts("Serveur port number"),
					"kind" => OWF_DAO_INPUT,
					"perm" => array("core:smtp"),
					"filter_cb" => array($this, "check_port"),
					"type" => WF_VARCHAR
				),
			),
		);
		
		$this->dao = new core_dao_form_db(
			$this->wf,
			OWF_DAO_ADD | OWF_DAO_REMOVE,
			$this->struct,
			"core_smtp",
			"Core SMTP DAO"
		);
	
	}
	
	public function check_description($item, $var) {
		if(strlen($var) < 2)
			return($this->lang->ts("Description too short"));

		return(true);
	}
	
	public function check_port($item, $var) {
		if($var <= 0)
			return($this->lang->ts("Port number too low"));
		if($var >= 65535)
			return($this->lang->ts("Port number too high"));
			
		return(true);
	}
	
	
	
}
