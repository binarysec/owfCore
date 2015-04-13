<?php

class core_poller extends wf_agg {
	
	public function loader() {
		$this->wf->core_dao();
		$this->session = $this->wf->session();
		
		$this->struct = array(
			"form" => array(),
			"data" => array(
				"id" => array(
					"type" => WF_INT | WF_AUTOINC | WF_PRIMARY | WF_UNSIGNED,
					"perm" => array("session:admin"),
					"name" => "ID",
				),
				"user_id" => array(
					"type" => WF_INT,
					"perm" => array("session:admin"),
					"name" => "User",
					"kind" => OWF_DAO_LINK_MANY_TO_ONE,
					"dao" => array($this->session->user, "get"),
					"field-id" => "id",
					"field-name" => array("firstname", " ", "name"),
				),
				"data" => array(
					"type" => WF_DATA,
					"perm" => array("session:admin"),
					"name" => "Data",
				),
			),
		);
		
		$this->dao = new core_dao_form_db(
			$this->wf,
			"core_poller",
			OWF_DAO_FORBIDDEN,
			$this->struct,
			"core_poller",
			"OWF Core poller events"
		);
	}
	
	public function poll() {
		$short = $this->wf->get_var("owfcp_short") || $this->wf->get_var("owfcps");
		$w = intval($this->wf->get_var("wait"));
		
		$timeout = 30;
		$wait = $w ? 1000 * min(max($w, 500), 3000) : 1000;
		$uid = $this->session->session_me["id"];
		$events = array();
		
		if($short) {
			$polled_events = $this->dao->get(array("user_id" => $uid));
			foreach($polled_events as $v)
				$events[] = unserialize($v["data"]);
		}
		else {
			do {
				$polled_events = $this->dao->get(array("user_id" => $uid));
				foreach($polled_events as $v)
					$events[] = unserialize($v["data"]);
				
				$timeout--;
				if($timeout)
					usleep($wait);
			} while(empty($events) && $timeout);
		}
		
		foreach($polled_events as $v)
			$this->dao->remove(array("id" => $v["id"]));
		
		$moar = $this->wf->execute_hook("owf_core_poller");
		
		foreach($moar as $result)
			$events[] = $result;
		
		echo json_encode($events);
		exit(0);
	}
	
	public function add($json, $uid = 0) {
		if(!$uid)
			$uid = $this->session->session_me["id"];
		
		$this->dao->add(array(
			"user_id" => $uid,
			"data" => serialize($json)
		));
	}
}
