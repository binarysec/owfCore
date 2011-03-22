<?php


class core_smtp extends wf_agg {
	public $wf;
	public $struct;
	public $dao;
	
	public function loader($wf) {
		$this->wf = $wf;
		$this->wf->core_dao();
		
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
					"name" => "Server description",
					"kind" => OWF_DAO_INPUT,
					"perm" => array("core:smtp"),
// 					"filter_cb" => array($this, "check_directory"),
					"type" => WF_VARCHAR
				),
				"server_ip" => array(
					"size" => 22,
					"name" => "Server IP or Hostname",
					"kind" => OWF_DAO_INPUT,
					"perm" => array("core:smtp"),
// 					"filter_cb" => array($this, "check_name"),
					"type" => WF_VARCHAR
				),
				"server_port" => array(
					"size" => 10,
					"value" => 25,
					"name" => "Serveur port number",
					"kind" => OWF_DAO_INPUT,
					"perm" => array("core:smtp"),
// 					"filter_cb" => array($this, "check_name"),
					"type" => WF_VARCHAR
				),
			),
		);
		
		$this->dao = new core_dao_item(
			$this->wf,
			OWF_DAO_ADD | OWF_DAO_REMOVE,
			$this->struct,
			"core_smtp",
			"Core SMTP DAO"
		);

// 		/* create the root */
// 		$ret = $this->dao->get(array("id" => 1));
// 		if(count($ret) == 0) {
// 			$this->dao->add(array(
// 				"depend" => 0,
// 				"name" => "root"
// 			));
// 		}
	
	}
	
	public function check_directory($item, $var) {
		$result = $this->dao->get(array("id" => (int)$var));
		if(count($result) > 0)
			return(true);
		return("Directory doesn't exists");
	}
	
	public function check_name($item, $var) {
		if(strlen($var) > 0) 
			return(true);
		return("Invalid directory name");
	}

	public function get_directories($item, $val) {
		$result = $this->dao->get();
		$scan = array();
		$dir = array();
		
		foreach($result as $node) {
			$scan[$node["id"]] = $node;
			$dir[$node["id"]] = "/".$scan[$node["id"]]["name"];
		}
		
		foreach($result as $node) {
			$str = &$dir[$node["id"]];
			$depend = $node["depend"];
			while($depend != 0) {
				$str = $dir[$depend]."$str";
				
				$depend = $scan[$depend]["depend"];
			}
		}

		return($dir);
	}
	
	
	
}
