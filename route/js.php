<?php

class wfr_core_js extends wf_route_request {
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * constructor
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function __construct($wf) {
		$this->wf = $wf;
		$this->a_core_request = $this->wf->core_request();
		$this->a_core_js = $this->wf->core_js();
	}

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * function to display css
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function show_js() {
		/* prend les données du fichiers */
		$file = $this->a_core_request->get_argv(0);
		$data = $this->a_core_js->get_content($file);

		/* check le tout */
		if(!$data) {
			header("Location: ".$this->wf->linker("/"));
			exit(0);
		}
			
		/* cache local */
		$requested_time = $_SERVER['HTTP_IF_MODIFIED_SINCE'];
		$cache_time = $this->a_core_js->get_last_modified($file);

		if($cache_time == $requested_time) {
			$this->a_core_request->set_header(
				$_SERVER['SERVER_PROTOCOL']." 304 Not Modified", 
				$cache_time
			);
			$this->a_core_request->send_headers();
			exit(0);
		}

		/* tout est ok on set le header et on envoi les données */
		$this->a_core_request->set_header(
			"Last-modified", $cache_time
		);
		$this->a_core_request->set_header(
			"Content-type", "application/x-javascript"
		);
		$this->a_core_request->set_header(
			"Content-length", strlen($data)
		);
		$this->a_core_request->send_headers();
		echo $data;
		exit(0);
	}

}

?>