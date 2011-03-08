<?php

define("OWF_DAO_INPUT",           1);
define("OWF_DAO_INPUT_REQ",       2);
define("OWF_DAO_DATA_DIR",        3);
define("OWF_DAO_UPLOAD",          4);
define("OWF_DAO_SELECT",          5);

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
