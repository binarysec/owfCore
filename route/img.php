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

		
		/* set the header and send data */
// 		$this->a_core_request->set_header("Last-modified", $cache_time);
// 		$this->a_core_request->set_header("Content-type", $this->a_core_img->get_mime_type($path));
// 		$this->a_core_request->set_header("Content-length", strlen($data));
// 		$this->a_core_request->send_headers();
// 		echo $data;
		exit(0);
	}

}

?>