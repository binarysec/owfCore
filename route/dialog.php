<?php

class wfr_core_dialog extends wf_route_request {

	public $wf;
	private $waf_site;
	private $lang;
	private $error = array();
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * constructor
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function __construct($wf) {
		$this->wf = $wf;
		$this->lang = $this->wf->core_lang()->get_context(
			"core/dialog"
		);
		$this->cipher = $this->wf->core_cipher();
	}
	
	public function show() {
		$type = $this->wf->get_var("type");
		
		
		$back = $this->wf->get_var("back");
		
		$title = $this->wf->get_var("title");
		$body = $this->wf->get_var("body");
		
		
	// confirmation
	// erreur
// 		type = confirm/error
		
		$tpl = new core_tpl($this->wf);
		$in = array(
			"title" => $title,
			"body" => $body,
		);	 
		$tpl->set_vars($in);
		$this->wf->admin_html()->set_title(htmlentities($title));
		$this->wf->admin_html()->rendering($tpl->fetch('core/dialog/confirm'));
		exit(0);
	}
	
	

}
