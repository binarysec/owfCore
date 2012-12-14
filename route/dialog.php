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
		$this->admin_html = $this->wf->admin_html();
		
	}
	
	public function show() {
		$data = $this->cipher->get_var("data");
		
		if($data['type'] == "confirm")
			$tpl = 'core/dialog/confirm';
		else if($data['type'] == "error")
			$tpl = 'core/dialog/error';
		else 
			exit(0);

		$this->admin_html->set_title(htmlentities($data['title']));
		
		$tpl = new core_tpl($this->wf);
		$in = array(
			"title" => $data['title'],
			"body" => $data['body'],
		);	 
		$tpl->set_vars($in);
		
		$this->admin_html->rendering($tpl->fetch($tpl));
		exit(0);
	}
	

}
