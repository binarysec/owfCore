<?php

class wfm_core extends wf_module {
	public function __construct($wf) {
		$this->wf = $wf;
	}
	/* mimetype icon (c) FAMFAMFAM */
	public function get_name() { return("core"); }
	public function get_description()  { return("OWF Native Core module"); }
	public function get_banner()  { return("OWF Native Core/1.3.0"); }
	public function get_version() { return("1.3.0"); }
	public function get_authors() { return("Michael VERGOZ"); }
	public function get_depends() { return(NULL); }
	
	public function session_permissions() {
		return(array(
			"core:smtp" => $this->ts("Allow SMTP configuration"),
		));
	}
	
	public function get_actions() {
		return(array(
			
			/* SMTP */
			"/admin/system/smtp" => array(
				WF_ROUTE_ACTION,
				"admin/system/smtp",
				"show",
				$this->ts("SMTP Service configuration"),
				WF_ROUTE_SHOW,
				array("core:smtp")
			),
			
			/* data listing */
			"/admin/system/data" => array(
				WF_ROUTE_REDIRECT,
				"/data",
				$this->ts("Listing des données"),
				WF_ROUTE_SHOW,
				array("session:god")
			),
			"/data" => array(
				WF_ROUTE_ACTION,
				"data",
				"show_data",
				$this->ts("Données statiques"),
				WF_ROUTE_HIDE,
				array("session:ranon")
			),
			
			/* dao */
			"/dao" => array(
				WF_ROUTE_ACTION,
				"dao",
				"show",
				$this->ts("Add form DAO"),
				WF_ROUTE_HIDE,
				array("session:ranon")
			),
			"/dao/gmap" => array(
				WF_ROUTE_ACTION,
				"dao",
				"gmap",
				"",
				WF_ROUTE_HIDE,
				array("session:ranon")
			),
			
			/* others */
			"/dialog" => array(
				WF_ROUTE_ACTION,
				"dialog",
				"show",
				$this->ts("Core dialog"),
				WF_ROUTE_HIDE,
				array("session:ranon")
			),
			"/json" => array(
				WF_ROUTE_ACTION,
				"json",
				"handler",
				$this->ts("JSON interface"),
				WF_ROUTE_HIDE,
				array("session:ranon")
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
	
	public function owf_post_init() {
		if(strlen(ini_get("date.timezone")) < 1)
			date_default_timezone_set("UTC");
	}
}
