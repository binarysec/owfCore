<?php
 
class wfm_core extends wf_module {
	public function __construct($wf) {
		$this->wf = $wf;
	}
	
	public function get_name() { return("core"); }
	public function get_description()  { return("OWF Native Core module"); }
	public function get_banner()  { return("OWF Native Core/1.2.0"); }
	public function get_version() { return("1.2.0"); }
	public function get_authors() { return("Michael VERGOZ"); }
	public function get_depends() { return(NULL); }
	
	public function get_actions() {
		return(array(

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
				$this->ts("Listing des données"),
				WF_ROUTE_SHOW,
				array("session:god")
			),
			"/admin/system/preferences/smtp" => array(
				WF_ROUTE_ACTION,
				"smtp",
				"page",
				$this->ts("SMTP Service configuration"),
				WF_ROUTE_SHOW,
				array("core:smtp")
			),
			"/data" => array(
				WF_ROUTE_ACTION,
				"data",
				"show_data",
				$this->ts("Données statiques"),
				WF_ROUTE_HIDE,
				array("session:anon")
			),
			"/dao/form" => array(
				WF_ROUTE_ACTION,
				"dao",
				"form",
				$this->ts("Add form DAO "),
				WF_ROUTE_HIDE,
				array("session:anon")
			),
		));
	}
	
	public function admin_partners() {
		return(array(
			array(
				"name" => "SAS BinarySEC",
				"url" => "http://www.binarysec.com/",
				"img" => "/data/admin/partners/binarysec.png"
			),
		));
	}
	
}
