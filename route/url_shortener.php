<?php

class wfr_core_url_shortener extends wf_route_request {
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * constructor
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function __construct($wf) {
		$this->wf = $wf;
		$this->a_core_request = $this->wf->core_request();
		$this->cipher = $this->wf->core_cipher();
	}
	
	public function redirect() {
		$url = $this->a_core_request->get_argv(0);
		if(!$url)
			$this->wf->redirector($this->wf->linker("/"));
		$url = $this->cipher->decode($url);
		$this->wf->redirector($url);
	}
}
