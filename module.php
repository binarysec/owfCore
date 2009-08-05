<?php
 
class wfm_core extends wf_module {
	public function __construct($wf) {
		$this->wf = $wf;
	}
	
	public function get_name() { return("core"); }
	public function get_description()  { return("OWF Core module"); }
	public function get_banner()  { return("OWF Core/1.1.0-HEAD"); }
	public function get_version() { return("1.1.0-HEAD"); }
	public function get_authors() { return("Michael VERGOZ"); }
	public function get_depends() { return(NULL); }
	
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
			"/session/logout" => array(
				WF_ROUTE_ACTION,
				"session",
				"logout",
				"Logout",
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
			),
			"/admin/system/data" => array(
				WF_ROUTE_REDIRECT,
				"/data",
				"Listing des données",
				WF_ROUTE_SHOW,
				array("session:god")
			),
			"/data" => array(
				WF_ROUTE_ACTION,
				"data",
				"show_data",
				"Données statiques",
				WF_ROUTE_HIDE,
				array("session:anon")
			),
		));
	}
}
