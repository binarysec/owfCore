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
	
	private function draw_form($item, $data=array()) {
		$result = array();
		/* follow and build form */
		foreach($item->data as $key => $val) {
		
			/* check permissions */
			$ret = $this->a_session->check_permission($val["perm"]);
		
			if($ret && $val["perm"]) {
				if(isset($val["kind"])) {
					$result[$key] = array(
						"text" => $val["name"],
						"kind" => $val["kind"],
					);
					if(isset($data[$key])) {
						$result[$key]["value"] = htmlentities($data[$key]);
					}
					else {
						if(array_key_exists("value", $val))
							$result[$key]["value"] = htmlentities($val["value"]);
					}
					
					if(array_key_exists("size", $val))
						$result[$key]["size"] = $val["size"];
						
					if($val["kind"] == OWF_DAO_SELECT) {
						if(isset($val["select_cb"]))
							$list = call_user_func($val["select_cb"], $item, $val);
					
						$result[$key]["list"] = $list;
					}
					if(isset($val["reader_cb"]))
						$result[$key]["value"] = call_user_func($val["reader_cb"], $item, $data[$key]);
				}
			}
		}	
		echo json_encode($result);
		exit(0);
	}
	
	public function form() {
		$this->mode = $this->a_core_request->get_argv(0);
		$this->agg = $this->a_core_request->get_argv(1);
		$this->id = (int)$this->a_core_request->get_argv(2);
		
		/* select form */
		$item = $this->selector();
		if(!is_object($item)) {
			$this->wf->display_error(
				404,
				"Data access object not found"
			);
			exit(0);
		}
		
		/* check form permission */
		$ret = $this->a_session->check_permission($item->struct["form"]["perm"]);
		if(!$ret || !isset($item->struct["form"]["perm"])) {
			$this->wf->display_error(
				403,
				"Data access object forbidden"
			);
			exit(0);
		}
			
		if($this->mode == 'add') {
			$this->draw_form($item);
		}
		else if($this->mode  == 'postadd') {
			$this->add_post($item);
		}
		else if($this->mode == 'mod') {
			$id = $this->wf->get_var("id");
			$ret = $item->get(array("id" => (int)$id));
			if(!array_key_exists(0, $ret))
				exit(0);
			
			$this->draw_form($item, $ret[0]);
		}
		else if($this->mode  == 'postmod') {
			$id = $this->wf->get_var("id");
			$ret = $item->get(array("id" => (int)$id));
			if(!array_key_exists(0, $ret))
				exit(0);
				
			$this->add_post($item, $id);
		}
		else if($this->mode  == 'get') {
			$this->get($item);
		}
		else if($this->mode  == 'del') {
			$id = $this->wf->get_var("id");
			$item->remove(array("id" => (int)$id));
			exit(0);
		}
	}

	public function add_post($item, $id=NULL) {
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
						else if($ret && $var) {
							if(isset($val["return_cb"])){
								$ret2 = call_user_func($val["return_cb"], $item, &$var);
								if($ret2)
									$insert[$key] = $ret2;
								else
									$error[$key] = $ret2;
							}else 
								$insert[$key] = $var;
						}
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
			if(isset($id))
				$item->modify(array("id" => $id), $insert);
			else
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
