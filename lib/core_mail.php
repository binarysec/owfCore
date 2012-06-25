<?php

class core_mail {
	private $wf;
	private $core_smtp;
	private $core_lang;
	
	private $headers = array();
	private $body;
	
	public function __construct($wf) {
		$this->wf = $wf;
		$this->core_smtp = $this->wf->core_smtp();
		$this->core_lang = $this->wf->core_lang();
	}
	
	public function set_body($body) {
		$this->body = $body;
	}
	
	public function add_header($header_name, $data) {
		$this->headers[$header_name] = $data;
	}
	
	public function set_headers(array $headers) {
		$this->headers = $headers;
	}
	
	public function merge_headers(array $headers) {
		$this->headers = array_merge($this->headers, $headers);
	}
}