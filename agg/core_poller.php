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
	private $actions = array();
	
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
		$this->route = $this->wf->get_var("owfCorePollerReferer");
		
		$this->wait_time = intval($this->wf->get_var("owfCoreLongPollWait"));
		$this->wait_time = 1000 * ($this->wait_time ?
			1000 * min(max($this->wait_time, 500), 3000) : 1000);
		
		/* callbacks */
		$callback_actions = $this->wf->execute_hook("owf_core_poller_actions");
		foreach($callback_actions as $actions) {
			foreach($actions as $event => $action) {
				if(	!array_key_exists("agg", $action) ||
					!array_key_exists("method", $action)
					) {
						throw new wf_exception(null, WF_EXC_PRIVATE,
							"Wrong core poller action : ".var_export($action, true)
						);
				}
				
				$this->actions[$event] = $action;
			}
		}
		
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
			$this->wf->display_error(400, "Parameter is missing", true);
		}
		
		$events = $events_to_process = array();
		
		/* short polling : fetch events */
		if($this->is_short) {
			$polled_events = $this->get_events();
			foreach($polled_events as $v)
				$events_to_process[] = $v;
		}
		
		/* long polling : loop until events or timeout */
		else {
			do {
				$polled_events = $this->get_events();
				foreach($polled_events as $v)
					$events_to_process[] = $v;
				
				$timeout--;
				if($timeout) {
					usleep($this->wait_time);
				}
				
			} while(empty($events_to_process) && $timeout);
		}
		
		foreach($events_to_process as $k => $v) {
			
			/* process event */
			switch($v["type"]) {
				case CORE_POLLER_RAW :
					$events[$k] = $v["data"];
					break;
				
				case CORE_POLLER_CALLBACK :
					$ret = false;
					$fct = null;
					$params = array();
					$cb_info = unserialize($v["data"]);
					
					if(is_array($cb_info)) {
						$fct = $this->actions[array_shift($cb_info)];
						$params = $cb_info;
					}
					elseif(is_string($cb_info) && array_key_exists($cb_info, $this->actions)) {
						$fct = $this->actions[$cb_info];
					}
					
					if($fct) {
						$is_url_ok = true;
						
						if($this->route && array_key_exists("url", $fct)) {
							
							$is_url_ok = false;
							
							if(is_string($fct["url"])) {
								$fct["url"] = array($fct["url"]);
							}
							
							foreach($fct["url"] as $url) {
								if(strstr($this->route, $url)) {
									$is_url_ok = true;
									break;
								}
							}
						}
						
						if($is_url_ok) {
							$ret = call_user_method_array($fct["method"], $this->wf->$fct["agg"](), $params);
							
							if($ret !== false) {
								$events[$k] = $ret;
							}
						}
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
				if(	(is_string($data) && !array_key_exists($data, $this->actions)) ||
					(is_array($data) && !array_key_exists($data[0], $this->actions))
					) {
						return false;
				}
			
			case CORE_POLLER_JSON :
			default :
				return serialize($data);
		}
		
	}
}
