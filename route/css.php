<?php

class wfr_wf_core_css extends wf_route_request {
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * constructor
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function __construct($wf) {
		$this->wf = $wf;
		$this->a_core_request = $this->wf->core_request();
		$this->a_core_css = $this->wf->core_css();

	}

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * function to display css
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function show_css() {
		/* prend les données du fichiers */
		$file = $this->a_core_request->channel[_CORE_ROUTE_UNKNOWN][0];
		$data = $this->a_core_css->get_content($file);

		/* cache local */
		$requested_time = $_SERVER['HTTP_IF_MODIFIED_SINCE'];
		$cache_time = $this->a_core_css->get_last_modified($file);

		if($cache_time == $requested_time) {
			$this->a_core_request->set_header(
				$_SERVER['SERVER_PROTOCOL']." 304 Not Modified", 
				$cache_time
			);
			$this->a_core_request->send_headers();
			exit(0);
		}

		/* check le tout */
		if(!$data)
			exit(0);
		
		/* tout est ok on set le header et on envoi les données */
		$this->a_core_request->set_header("Last-modified", $cache_time);
		$this->a_core_request->set_header("Content-type", "text/css");
		$this->a_core_request->set_header("Content-length", strlen($data));
		$this->a_core_request->send_headers();
		echo $data;
		exit(0);
	}

}

?>