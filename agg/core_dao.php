<?php

define("OWF_DAO_INPUT",				1);
define("OWF_DAO_INPUT_READON",		        2);
define("OWF_DAO_NUMBER",			3);
define("OWF_DAO_NUMBER_READON",		        4);
define("OWF_DAO_UPLOAD",			5);
define("OWF_DAO_SELECT",			6);
define("OWF_DAO_HIDDEN",			7);
define("OWF_DAO_RADIO",				8);
define("OWF_DAO_RADIO_READON",	        	9);
define("OWF_DAO_CHECKBOX",			10);
define("OWF_DAO_CHECKBOX_READON",	        11);
define("OWF_DAO_SLIDER",			12);
define("OWF_DAO_FLIP",				13);
define("OWF_DAO_MAP",				16);

define("OWF_DAO_LINK_MANY_TO_ONE",		20);
define("OWF_DAO_LINK_MANY_TO_MANY",		21);

define("OWF_DAO_TEXT",				30);

// Not ported yet
//define("OWF_DAO_INPUT_REQ",		2); ??
//define("OWF_DAO_DATA_DIR",		4); ??
//define("OWF_DAO_DATE",           14);
//define("OWF_DAO_DATE_READON",    15);
//define("OWF_DAO_STARS",          17);

define("OWF_DAO_FORBIDDEN",	0x00);
define("OWF_DAO_ADD",		0x01);
define("OWF_DAO_REMOVE",	0x02);

class core_dao extends wf_agg {
	private $position = 0;
	private $registered = array();
	
	public function loader($wf) {
		$this->a_session = $this->wf->session();
		
		$this->wf->db->register_zone("core_dao",  "Core dao table", array(
			"id" => WF_INT | WF_PRIMARY,
			"name" => WF_VARCHAR,
		));
		
		/* align position to the next available */
		$q = new core_db_select("core_dao", array(), array());
		$this->wf->db->query($q);
		$res = $q->get_result();
		foreach($res as $dao)
			$this->position = max($this->position, $dao["id"]);
		$this->position++;
	}
	
	public function register($item) {
		
		/* sanatize dao object */
		$this->sanatize($item);
		
		/* check if dao exist */
		$q = new core_db_select("core_dao", array(), array("name" => $item->name));
		$this->wf->db->query($q);
		$res = current($q->get_result());
		
		/* if dao already registered in the past, then get the id */
		if($res && isset($res["name"])) {
			if(isset($this->registered[$res["id"]]))
				throw new wf_exception($this->wf, WF_EXC_PRIVATE,
					"You tried to load dao number $res[id] twice"
				);
			$this->registered[$res["id"]] = $item;
			$item->id = $res["id"];
		}
		/* otherwise get the next available id and insert */
		else {
			$this->registered[$this->position] = $item;
			$item->id = $this->position;
			$this->position++;
			
			$q = new core_db_insert("core_dao", array(
				"id" => $item->id,
				"name" => $item->name,
			));
			$this->wf->db->query($q);
		}
	}
	
	public function get() {
		return($this->registered);
	}
	
	public function draw_form($item, $data=array(), $json=true) {
		$result = array();
		
		/* follow and build form */
		foreach($item->data as $key => $val) {
			
			/* check permissions and required parameters */
			$ret = false;
			if(isset($val["perm"], $val["kind"]))
				$ret = $this->a_session->check_permission($val["perm"]);
			
			if($ret) {
				
				$result[$key] = array(
					"text" => $val["name"],
					"kind" => $val["kind"],
				);
				
				if(isset($data[$key]))
					$result[$key]["value"] = htmlspecialchars($data[$key]);
				elseif(array_key_exists("value", $val))
					$result[$key]["value"] = htmlspecialchars($val["value"]);
				
				/* ... */
				if(	$val["kind"] == OWF_DAO_SELECT ||
					$val["kind"] == OWF_DAO_RADIO ||
					$val["kind"] == OWF_DAO_RADIO_READON ||
					$val["kind"] == OWF_DAO_CHECKBOX ||
					$val["kind"] == OWF_DAO_CHECKBOX_READON
					) {
					if(isset($val["select_cb"]))
						$list = call_user_func($val["select_cb"], $item, $val);
					else
						$list = $val["list"];
					$result[$key]["list"] = $list;
				}
				elseif($val["kind"] == OWF_DAO_FLIP) {
					if(isset($val["texton"]))
						$result[$key]["texton"] = $val["texton"];
					if(isset($val["textoff"]))
						$result[$key]["textoff"] = $val["textoff"];
				}
				elseif($val["kind"] == OWF_DAO_SLIDER) {
					if(isset($val["startnum"]))
						$result[$key]["startnum"] = (int) $val["startnum"];
					
					if(isset($val["endnum"]))
						$result[$key]["endnum"] = (int) $val["endnum"];
					
					if(isset($val["step"]))
						$result[$key]["step"] = (int) $val["step"];
				}
				elseif($val["kind"] == OWF_DAO_MAP) {
					if(isset($data[$key."_latitude"]))
						$result[$key]["value_latitude"] = htmlspecialchars($data[$key."_latitude"]);
					
					if(isset($data[$key."_longitude"]))
						$result[$key]["value_longitude"] = htmlspecialchars($data[$key."_longitude"]);
				}
				elseif($val["kind"] == OWF_DAO_LINK_MANY_TO_ONE) {
					$result[$key]["list"] = array();
					$data = is_array($val["dao"]) ? 
						call_user_func($val["dao"]) : $val["dao"]->get();
					foreach($data as $subdaoitem)
						$result[$key]["list"][$subdaoitem[$val["field-id"]]] = $subdaoitem[$val["field-name"]];
				}
				elseif($val["kind"] == OWF_DAO_LINK_MANY_TO_MANY) {
					$result[$key]["list"] = array();
					$data = is_array($val["dao"]) ? 
						call_user_func($val["dao"]) : $val["dao"]->get();
					foreach($data as $subdaoitem)
						$result[$key]["list"][$subdaoitem[$val["field-id"]]] = $subdaoitem[$val["field-name"]];
				}
				
				if(isset($val["reader_cb"], $data[$key]))
					$result[$key]["value"] = call_user_func($val["reader_cb"], $item, $data[$key]);
			}
		}
		
		if($json) {
			echo json_encode($result);
			exit(0);
		}
		else
			return($result);
		
	}
	
	private function sanatize($item) {
		$error = "";
		
		foreach($item->struct["data"] as $key => $val) {
			if(isset($val["kind"])) {
				if(	$val["kind"] == OWF_DAO_LINK_MANY_TO_ONE ||
					$val["kind"] == OWF_DAO_LINK_MANY_TO_MANY
					) {
						if(!isset($val["dao"], $val["field-id"], $val["field-name"]))
							$error = "-";
						elseif(is_array($val["dao"]) && !isset($val["type"]))
							$error = "-";
				}
				elseif($val["kind"] == OWF_DAO_MAP) {
				}
				else {
					if(!isset($val["type"]))
						$error = "-";
				}
			}
		}
		
		if(!empty($error)) {
			$error = "Malformed dao ".$item->name;
			throw new wf_exception($this->wf, WF_EXC_PRIVATE, $error);
		}
	}
}
