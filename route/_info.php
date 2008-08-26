<?php
 
class wfr_wf_core extends wf_route_info {

	public function __construct($wf) {
		$this->wf = $wf;
	}
	
	public function get_info() {
		return(array(
			"core",
			"Core",
			"BinarySEC SAS",
			"http://binaryec.com/"
		));
	}
	
	public function get_actions() {
		return(array(
			"/session/login" => array(
				WF_ROUTE_ACTION,
				"session",
				"login",
				"Login",
				WF_ROUTE_HIDE,
				array("session:anon")
			)
		));
	}
	
	
}

?>