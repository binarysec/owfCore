<?php

define("CORE_POLLER_JSON",		0);
define("CORE_POLLER_CALLBACK",	1);
define("CORE_POLLER_RAW",		2);

class core_poller_dao extends core_dao_form_db {
	
	public function add($data) {
		$data["create_time"] = time();
		return parent::add($data);
	}
	
}

class core_poller extends wf_agg {
	
	private $is_polling = false;
	
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
				"create_time" => array(
					"type" => WF_INT | WF_UNSIGNED,
					"perm" => array("session:admin"),
					"name" => "Create date",
					"kind" => OWF_DAO_DATETIME,
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
		
		$this->dao = new core_poller_dao(
			$this->wf,
			"core_poller",
			OWF_DAO_FORBIDDEN,
			$this->struct,
			"core_poller",
			"OWF Core poller events"
		);
		
		/* initialize options */
		$this->is_polling = $this->wf->get_var("owfCorePoller") ? true : false;
		$this->is_short = $this->wf->get_var("owfCoreShortPoll");
		$this->last_poll = intval($this->wf->get_var("owfCoreLastPoll"));
		
		$this->wait_time = intval($this->wf->get_var("owfCoreLongPollWait"));
		$this->wait_time = 1000 * ($this->wait_time ?
			1000 * min(max($this->wait_time, 500), 3000) : 1000);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Clear old events
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function clear($old = 300) {
		
		$q = new core_db_adv_delete("core_poller");
		$q->do_comp("create_time", "<=", time() - $old);
		$this->wf->db->query($q);
		
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Get events to display
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function get_events($uid = 0) {
		
		if(!$uid)
			$uid = $this->session->session_me["id"];
		
		$q = new core_db_adv_select("core_poller");
		$q->do_comp("user_id", "=", $uid);
		$q->do_comp("create_time", ">", $this->last_poll);
		$this->wf->db->query($q);
		
		return $q->get_result();
		
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * The poll call done by the client to retrieve events
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function poll($timeout = 30) {
		
		if(!$this->last_poll) {
			echo json_encode(array("time" => time(), "events" => array()));
			exit(0);
		}
		
		$events = array();
		
		/* short polling : fetch events */
		if($this->is_short) {
			$polled_events = $this->get_events();
			foreach($polled_events as $v)
				$events[] = $v;
		}
		
		/* long polling : loop until events or timeout */
		else {
			do {
				$polled_events = $this->get_events();
				foreach($polled_events as $v)
					$events[] = $v;
				
				$timeout--;
				if($timeout) {
					usleep($this->wait_time);
				}
				
			} while(empty($events) && $timeout);
		}
		
		foreach($events as $k => $v) {
			
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
		
		echo json_encode(array(
			"time" => time(),
			"events" => $events
		));
		
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
			if($this->session->is_online($uid)) {
				$this->dao->add(array(
					"user_id"	=> $uid,
					"data"		=> $data,
					"type"		=> $type
				));
			}
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
