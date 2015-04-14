<?php

define("CORE_POLLER_JSON",		0);
define("CORE_POLLER_CALLBACK",	1);
define("CORE_POLLER_RAW",		2);

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
				"type" => array(
					"type" => WF_SMALLINT | WF_UNSIGNED,
					"perm" => array("session:admin"),
					"name" => "Type",
					"kind" => OWF_DAO_SELECT,
					"list" => array(
						CORE_POLLER_JSON		=> "JSON data",
						CORE_POLLER_CALLBACK	=> "Callback",
						CORE_POLLER_RAW			=> "Raw data",
					)
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
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * The poll call done by the client to retrieve events
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function poll() {
		$short = $this->wf->get_var("owfcp_short") || $this->wf->get_var("owfcps");
		$w = intval($this->wf->get_var("wait"));
		
		$timeout = 30;
		$wait = $w ? 1000 * min(max($w, 500), 3000) : 1000;
		$uid = $this->session->session_me["id"];
		$events = array();
		
		/* short polling : fetch events */
		if($short) {
			$polled_events = $this->dao->get(array("user_id" => $uid));
			foreach($polled_events as $v)
				$events[] = $v;
		}
		
		/* long polling : loop until events or timeout */
		else {
			do {
				$polled_events = $this->dao->get(array("user_id" => $uid));
				foreach($polled_events as $v)
					$events[] = $v;
				
				$timeout--;
				if($timeout)
					usleep($wait);
			} while(empty($events) && $timeout);
		}
		
		foreach($events as $k => $v) {
			
			/* remove event from database */
			$this->dao->remove(array("id" => $v["id"]));
			
			/* process event */
			switch($v["type"]) {
				case CORE_POLLER_RAW :
					$events[$k] = $v["data"];
					break;
				case CORE_POLLER_CALLBACK :
					$cb_info = unserialize($v["data"]);
					if(is_array($cb_info)) {
						$class = array_shift($cb_info);
						$method = array_shift($cb_info);
						array_unshift($cb_info, $this->wf);
						$ret = call_user_func_array(array($class, $method), $cb_info);
					}
					elseif(is_string($cb_info)) {
						$ret = $cb_info($this->wf);
					}
					
					if($ret !== false) {
						$events[$k] = $ret;
					}
					
					break;
				case CORE_POLLER_JSON :
				default :
					$events[$k] = unserialize($v["data"]);
					break;
			}
			
		}
		
		$moar = $this->wf->execute_hook("owf_core_poller");
		
		foreach($moar as $result)
			$events[] = $result;
		
		echo json_encode($events);
		exit(0);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Add an event to the polling system which will be delivered to the end client
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function add($data, $uid = 0, $type = CORE_POLLER_JSON) {
		if(!$uid)
			$uid = $this->session->session_me["id"];
		
		$data = $this->format_data($data, $type);
		
		if($data !== false) {
			$this->dao->add(array(
				"user_id"	=> $uid,
				"data"		=> $data,
				"type"		=> $type
			));
		}
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Format data before database insertion
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function format_data($data, $type) {
		
		switch($type) {
			case CORE_POLLER_RAW :
				return $data;
			case CORE_POLLER_CALLBACK :
				if(	(is_string($data) && !function_exists($data)) ||
					(is_array($data) && !method_exists($data[0], $data[1]))
					) {
						return false;
				}
			case CORE_POLLER_JSON :
			default :
				return serialize($data);
		}
		
	}
}
