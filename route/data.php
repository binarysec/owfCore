<?php

class wfr_core_data extends wf_route_request {
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * constructor
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function __construct($wf) {
		$this->wf = $wf;
		$this->a_core_request = $this->wf->core_request();
		$this->a_core_file = $this->wf->core_file();
	}

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * function to display file
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function show_data() {
		$exts = array(
			'.png' => 'image/png',
			'.jpeg' => 'image/jpeg',
			'.gif' => 'image/gif',
			'.js' => 'application/x-javascript',
			'.css' => 'text/css'
		);
		
		$tmp = explode('/', $this->a_core_request->get_ghost());
		/* Module name */
		$mod = $tmp[1];
		
		if(!isset($this->wf->modules[$mod])) {
			exit(0);
		}
		
		$tmp[0] = $tmp[1] = NULL;
		/* Get the uri to the file */
		$uri = substr(implode('/', $tmp), 2);
		
		$base = realpath($this->wf->modules[$mod][0].'/var/data/');
		$path = realpath($this->wf->modules[$mod][0].'/var/data/'.$uri);
		
		/* Get requested file extension */
		$file_ext = explode('.', $tmp[count($tmp)-1]);
		$file_ext = $file_ext[count($file_ext)-1];
		
		/* File type */
		$type = $exts['.'.$file_ext];
		
		if(!file_exists($path)) {
			exit(0);
		}
		
		if(substr($path, 0, strlen($base)) != $base) {
			exit(0);
		}
		
		$mtime = filemtime($path);
		$file_time = array(
			$mtime,
			date("D, d M Y H:i:s \G\M\T", $mtime)
		);
		
		$requested_time = $_SERVER['HTTP_IF_MODIFIED_SINCE'];

		if($file_time == $requested_time) {
			$this->a_core_request->set_header(
				$_SERVER['SERVER_PROTOCOL']." 304 Not Modified", 
				$file_time
			);
			$this->a_core_request->send_headers();
			exit(0);
		}
		
		$this->a_core_request->set_header("Last-modified", $cache_time);
		$this->a_core_request->set_header("Content-type", $type);
		$this->a_core_request->set_header("Content-length", strlen($data));
		$this->a_core_request->send_headers();
		
		readfile($path);
		
		exit(0);
	}

}

?>