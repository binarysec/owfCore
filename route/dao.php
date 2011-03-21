<?php

class wfr_core_dao extends wf_route_request {
	private $a_session;
	private $a_core_request;
	private $a_core_dao;
	
	private $mode;
	private $agg;
	private $id;
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * constructor
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function __construct($wf) {
		$this->wf = $wf;
		$this->a_core_request = $this->wf->core_request();
		
		$this->a_session = $this->wf->session();
		$this->a_core_dao = $this->wf->core_dao();
		$this->a_core_request = $this->wf->core_request();
	}

	private function selector() {
		/* load aggregator */
		$agg = $this->wf->load_agg($this->agg);
		$dao = $this->a_core_dao->get();
		
		/* check if the aggregator exists */
		if(array_key_exists($this->id, $dao)) {
			if(is_object($dao[$this->id]))
				return($dao[$this->id]);
		}
		return(TRUE);
	}
	
	private function draw_form($item) {
// 		echo 
// 			'<form id="'.
// 			$item->name.$item->id.
// 			'_form" action="/">'.
// 			"<table>\n";
		$result = array();
		
		/* follow and build form */
		foreach($item->data as $key => $val) {
		
			/* check permissions */
			$ret = $this->a_session->check_permission($val["perm"]);
			if($ret && $val["perm"]) {
				$result[$key] = array(
					"text" => $val["name"],
					"kind" => $val["type"],
				);
				
				if($val["kind"] == OWF_DAO_SELECT) {
					if(isset($val["select_cb"]))
						$list = call_user_func($val["select_cb"], $item, $val);
					
					$result[$key]["list"] = $list;
				}
				
				
			}
		}

		echo json_encode($result);
		
		exit(0);
		
	}
	
// 				if($val["type"] == OWF_DAO_INPUT) {
// 					echo 
// 						"<tr>\n".
// 						'<td><span id="'.$key.'_id">'.$val["name"].': </span></td>'.
// 						'<td><input name="'.$key.'" type="text"></td>'.
// 						"</tr>\n";
// 				}
// 				else if($val["kind"] == OWF_DAO_SELECT) {
// 					if(isset($val["select_cb"]))
// 						$list = call_user_func($val["select_cb"], $item, $val);
// 		
// 					echo 
// 						"<tr>\n".
// 						"<td>$val[name]: </td>".
// 						'<td><select name="'.$key.'">';
// 
// 					foreach($list as $id => $entry) {
// 						echo "<option value=\"$id\">".
// 							$entry.
// 							"</option>\n";
// 					}
// 					
// 					echo '</select></td>'.
// 						"</tr>\n";

	public function form() {
		$this->mode = $this->a_core_request->get_argv(0);
		$this->agg = $this->a_core_request->get_argv(1);
		$this->id = (int)$this->a_core_request->get_argv(2);
		
		$item = $this->selector();
		if(!is_object($item)) {
			$this->wf->display_error(
				404,
				"Data access object not found"
			);
			exit(0);
		}
		
		
		if($this->mode == 'add') {
			$this->draw_form($item);
		}
		else if($this->mode  == 'postadd') {
			$this->add_post($item);
		}
		else if($this->mode  == 'get') {
			$this->get($item);
		}
	}

	public function add_post($item) {
		$insert = array();
		$error = array();
		
		/* read variable */
		foreach($item->data as $key => $val) {
			/* check permission */
			$ret = $this->a_session->check_permission($val["perm"]);
			if($ret && $val["perm"]) {
				if(isset($val["kind"])) {
					/* get var */
					$var = $this->wf->get_var($key);
					
					/* execute filter */
					if(isset($val["filter_cb"])) {
						$ret = call_user_func($val["filter_cb"], $item, &$var);
						if(is_string($ret))
							$error[$key] = $ret;
						else if($ret && $var)
							$insert[$key] = $var;
					}
					else if($var)
						$insert[$key] = $var;
				}
				
				if(count($error) > 0 && !array_key_exists($key, $error)) {
					$error[$key] = false;
				}
			}
		}
		
		if(count($insert) > 0 && count($error) == 0) {
			$item->add($insert);
			echo json_encode(true);
			return;
		}
		else if(count($error) == 0)
			$error["__dao_title"] = "No data to insert";
		
		echo json_encode($error);
		return;
	}
	
	public function get($item) {
		$_where = $this->wf->get_var("where");
// 		$_order = $this->wf->get_var("order");
		$where = array();
// 		$order = array();

		/* read variable */
		if(is_array($_where)) {
			foreach($item->data as $key => $val) {
				/* check permission */
				$ret = $this->a_session->check_permission($val["perm"]);
				if($ret) {
					$var = &$_where[$key];
					if(isset($var))
						$where[$key] = $var;
				}	
			}
		}
		
		$res = $item->get($where);		
		echo json_encode($res);		
		exit(0);
	}
	
	
}
