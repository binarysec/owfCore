<?php

class core_mail {
	private $wf;
	private $core_smtp;
	private $core_lang;
	
	private $headers = array();
	private $body;
	private $template = "var/tpl/core/mail/base";
	
	public function __construct($wf, $mail_from, $rcpt_to, $subject, $content) {
		$this->wf = $wf;
		$this->core_smtp = $this->wf->core_smtp();
		$this->core_lang = $this->wf->core_lang();
	}
	
	public function add_header($header_name, $data) {
		$this->headers[$header_name] = $data;
	}
	
	public function attach($file) {

	}

	public function render($file=null) {

	}

	public function send() {

	}

}
