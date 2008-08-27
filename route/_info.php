<?php
 
class _wfr_core extends wf_route_info {

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
			),
			"/img" => array(
				WF_ROUTE_ACTION,
				"img",
				"show_img",
				"Img",
				WF_ROUTE_HIDE,
				array("session:anon")
			),
			"/css" => array(
				WF_ROUTE_ACTION,
				"css",
				"show_css",
				"Css",
				WF_ROUTE_HIDE,
				array("session:anon")
			),
			"/js" => array(
				WF_ROUTE_ACTION,
				"js",
				"show_js",
				"Js",
				WF_ROUTE_HIDE,
				array("session:anon")
			)
		));
	}
	
	
}

?>