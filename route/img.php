<?php

class wfr_core_img extends wf_route_request {
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * constructor
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function __construct($wf) {
		$this->wf = $wf;
		$this->a_core_request = $this->wf->core_request();
		$this->a_core_img = $this->wf->core_img();
	}

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * function to display images
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function show_img() {
		$file = $this->a_core_request->get_ghost();
		
		/* prend et vérifie le last modified */
		$lastmod = $this->a_core_img->get_last_modified($file);
		if(!$lastmod) {
			header("Location: ".$this->wf->linker("/"));
			exit(0);
		}
		
		/* cache local */
		$requested_time = $_SERVER['HTTP_IF_MODIFIED_SINCE'];
		$cache_time = $lastmod[1];

		if($cache_time == $requested_time) {
			$this->a_core_request->set_header(
				$_SERVER['SERVER_PROTOCOL'].
				" 304 Not Modified", 
				$cache_time
			);
			$this->a_core_request->send_headers();
			exit(0);
		}

		/* set the header and send data */
		$this->a_core_request->set_header(
			"Last-modified", 
			$cache_time
		);
		$this->a_core_request->set_header(
			"Content-type",
			$this->a_core_img->get_mime_type($file)
		);
		$this->a_core_request->set_header(
			"Content-length", 
			$this->a_core_img->get_size($file)
		);
		$this->a_core_request->send_headers();

		/* output */
		$this->a_core_img->show_image($file);
		exit(0);
	}

}

?>