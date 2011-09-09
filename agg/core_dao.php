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

class core_dao extends wf_agg {
	private $position = 0;
	private $registered = array();
	
	public function loader($wf) {
		$this->wf = $wf;
	}
	
	public function register($item) {
		$this->registered[$this->position] = $item;
		$item->id = $this->position;
		$this->position++;
	}
	
	public function get() {
		return($this->registered);
	}
}
