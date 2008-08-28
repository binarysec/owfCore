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
		/* fetch data */
		$chan = $this->a_core_request->get_args();
		$mod = $chan[0];
		$chan[0] = NULL;
		$file = substr(implode('/', $chan), 1);
		echo $file;
		$path = $this->a_core_img->construct_path($mod, $file);

		/* doesn't exist */
		if(!$path) {
			$this->a_core_request->set_header(
				$_SERVER['SERVER_PROTOCOL']." 404 Not Found"
			);
			$this->a_core_request->send_headers();
			exit(0);
		}

		$data = $this->a_core_img->get_content($path);

		/* local cache */
		$requested_time = $_SERVER['HTTP_IF_MODIFIED_SINCE'];
		$file_time = $this->a_core_img->get_last_modified($path);

		if($file_time == $requested_time) {
			$this->a_core_request->set_header(
				$_SERVER['SERVER_PROTOCOL']." 304 Not Modified", 
				$file_time
			);
			$this->a_core_request->send_headers();
			exit(0);
		}

		/* check all */
		if(!$data)
			exit(0);
		
		/* set the header and send data */
		$this->a_core_request->set_header("Last-modified", $cache_time);
		$this->a_core_request->set_header("Content-type", $this->a_core_img->get_mime_type($path));
		$this->a_core_request->set_header("Content-length", strlen($data));
		$this->a_core_request->send_headers();
		echo $data;
		exit(0);
	}

}

?>