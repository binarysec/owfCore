<?php


class wfm_core extends wf_module {
	public function __construct($wf) {
		$this->wf = $wf;
	}
	/* mimetype icon (c) FAMFAMFAM */
	public function get_name() { return("core"); }
	public function get_description()  { return("OWF Native Core module"); }
	public function get_banner()  { return("OWF Native Core/1.2.2"); }
	public function get_version() { return("1.2.2"); }
	public function get_authors() { return("Michael VERGOZ"); }
	public function get_depends() { return(NULL); }
	
	public function session_permissions() {
		return(array(
			"core:smtp" => $this->ts("Allow SMTP configuration"),
		));
	}
	
	public function get_actions() {
		return(array(

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
				array("session:ranon")
			),
			"/dao/form" => array(
				WF_ROUTE_ACTION,
				"dao",
				"form",
				$this->ts("Add form DAO "),
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
		$core_pref = $this->wf->core_pref()->register_group("core", "core");
		
		$core_pref->register(
			"site_name",
			"Nom du site",
			CORE_PREF_VARCHAR,
			isset($this->wf->ini_arr["common"]["site_name"]) ? 
				$this->wf->ini_arr["common"]["site_name"] :
				"http://www.mywebsite.com"
		);
		
		$core_pref->register(
			"base",
			"Racine du site",
			CORE_PREF_VARCHAR,
			isset($this->wf->ini_arr["common"]["base"]) ? 
				$this->wf->ini_arr["common"]["base"] : ""
		);
	}
}
