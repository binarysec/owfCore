<?php

define("OWF_DAO_INPUT",           1);
define("OWF_DAO_INPUT_REQ",       2);
define("OWF_DAO_INPUT_READON",    3);
define("OWF_DAO_DATA_DIR",        4);
define("OWF_DAO_UPLOAD",          5);
define("OWF_DAO_SELECT",          6);
define("OWF_DAO_HIDDEN",          7);
define("OWF_DAO_RADIO",           8);
define("OWF_DAO_RADIO_READON",    9);
define("OWF_DAO_DATE",           10);
define("OWF_DAO_DATE_READON",    11);
define("OWF_DAO_SLIDER",         12);
define("OWF_DAO_STARS",          13);

class core_dao extends wf_agg {
	private $position = 0;
	private $registered = array();
	
	public function loader($wf) {
		$this->a_session = $this->wf->session();
	}
	
	public function register($item) {
		$this->registered[$this->position] = $item;
		$item->id = $this->position;
		$this->position++;
	}
	
	public function get() {
		return($this->registered);
	}
	
	public function draw_form($item, $data=array(), $json=true) {
		$result = array();
		/* follow and build form */
		foreach($item->data as $key => $val) {
		
			/* check permissions */
			$ret = isset($val["perm"]) ? $this->a_session->check_permission($val["perm"]) : true;
		
			if($ret && isset($val["perm"])) {
				if(isset($val["kind"])) {
					$result[$key] = array(
						"text" => $val["name"],
						"kind" => $val["kind"],
					);
					if(isset($data[$key])) {
						$result[$key]["value"] = htmlspecialchars($data[$key]);
					}
					else {
						if(array_key_exists("value", $val))
							$result[$key]["value"] = htmlspecialchars($val["value"]);
					}
					
					if(array_key_exists("size", $val))
						$result[$key]["size"] = $val["size"];

					if($val["kind"] == OWF_DAO_SELECT) {
						if(isset($val["select_cb"]))
							$list = call_user_func($val["select_cb"], $item, $val);
						else
							$list = $val["list"];
						$result[$key]["list"] = $list;
					}
					elseif($val["kind"] == OWF_DAO_SLIDER) {
						if(isset($val["startnum"]))
							$result[$key]["startnum"] = (int) $val["startnum"];
						
						if(isset($val["endnum"]))
							$result[$key]["endnum"] = (int) $val["endnum"];
						
						if(isset($val["step"]))
							$result[$key]["step"] = (int) $val["step"];
					}
					
					if(isset($val["reader_cb"]) && array_key_exists($key, $data))
						$result[$key]["value"] = call_user_func($val["reader_cb"], $item, $data[$key]);
				}
			}
		}	
		if($json) {
			echo json_encode($result);
			exit(0);
		}
		else
			return($result);
		
	}
}
