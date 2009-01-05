<?php

class wfr_core_data extends wf_route_request {
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * constructor
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function __construct($wf) {
		$this->wf = $wf;
		$this->a_core_request = $this->wf->core_request();
		
		$this->a_core_html = $this->wf->core_html();
		$this->a_core_session = $this->wf->core_session();
	}


	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Public function
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function show_data() {
		$file = $this->a_core_request->get_ghost();
		
		/* remove directory traversal */
		/* Visiblement le serveur HTTP transpose le truc 
		   mais pour etre sur ... */
		$file = preg_replace(
			array(
				"/\/\.\./",
				"/\/\.\//"
			),
			array(),
			$file
		);
		if(!$this->show_file($file)) {
			/* fichier inconnu */
			if(!$this->is_dir($file)) {
				/* fichier vraiment inconnu */
				$this->wf->display_error(
					404,
					"Page not found"
				);
			}
			else {
				/* autorisé à afficher listing ? */
				/* begin: added by keo on 31/12/2008 */
				if(!$this->wf->ini_arr["common"]["allow_data_index"]) {
					$this->wf->display_error(
						403,
						"You don't have permission to access this page on this server"
					);
				}
				/* end: added by keo on 31/12/2008 */
				
				/* get permission */
				$is = $this->a_core_session->user_get_permissions(
					NULL, 
					WF_USER_GOD
				);
				if(!$is || !$this->wf->mod_exists("admin"))
					$this->wf->display_error(
						404,
						"Page not found"
					);
					
				$this->show_listing($file);
			}
		}
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Send a file if possible
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	private function show_file($file) {
		/* essaye de trouver le fichier */
		$modrev = array_reverse($this->wf->modules);
		foreach($modrev as $mod => $mod_infos) {
			$tmp = $this->wf->modules[$mod][0].
				'/var/data'.$file;
				
			if(is_file($tmp)) {
				$used_file = $tmp;
				break;
			}
		}
		
		/* fichier inconnu */
		if(!$used_file)
			return(FALSE);
		
		/* prend le type de contenu */
		$mime = $this->wf->core_mime()->get_mime(
			$used_file
		);
		
		/* construit le temps de génération */
		$mtime = filemtime($used_file);
		$file_time = date("D, d M Y H:i:s \G\M\T", $mtime);
		
		/* vérifie la requete */
		$requested_time = $_SERVER['HTTP_IF_MODIFIED_SINCE'];
		
		if($file_time == $requested_time) {
			$this->a_core_request->set_header(
				$_SERVER['SERVER_PROTOCOL'].
				" 304 Not Modified"
			);
			$this->a_core_request->send_headers();
			exit(0);
		}
		
		/* prepare les type de fichier */
		$this->a_core_request->set_header(
			"Last-modified",
			$file_time
		);
		
		$this->a_core_request->set_header(
			"Content-type", 
			$mime
		);
		
		/* Cache control by expires */
		$mtime = time()+3600;
		$expires = date("D, d M Y H:i:s \G\M\T", $mtime);
		$this->a_core_request->set_header(
			"Expires",
			$expires
		);
		$this->a_core_request->set_header(
			"Cache-Control",
			"max-age=3600"
		);
		
		$this->a_core_request->set_header(
			"Content-length", 
			filesize($used_file)
		);
		
		$this->a_core_request->send_headers();
		
		/* envoi le fichier */
		readfile($used_file);
		exit(0);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Check is the key is a directory
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	private function is_dir($file) {
		/* essaye de trouver le fichier */
		$modrev = array_reverse($this->wf->modules);
		foreach($modrev as $mod => $mod_infos) {
			$tmp = $this->wf->modules[$mod][0].
				'/var/data'.$file;
				
			if(is_dir($tmp)) {
				$used_file = $tmp;
				break;
			}
		}
		
		/* fichier inconnu */
		if(!$used_file)
			return(FALSE);
		return(TRUE);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Draw the listing
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	private function show_listing($_directory) {
		/* trouve s'il y a un répertoire supérieur */
		$directory = " ";
		$up_dir = NULL;
		$token = FALSE;
		for($a=strlen($_directory)-1, $b=0; $a>=0; $a--, $b++) {
			if($_directory[$a] != '/') {
				$directory[$a] = $_directory[$a];
				if($token)
					$up_dir .= $_directory[$a];
			}
			else if($_directory[$a] == '/' && $b > 0) {
				$directory[$a] = $_directory[$a];
				if(!$token)
					$token = TRUE;
				else
					$up_dir .= $_directory[$a];
			}
		}
		if(strlen($directory) <= 1)
			$directory = "/";
		else
			$directory = trim($directory);
		$up_dir = strrev($up_dir);
		
		/* scan thr directory */
		$d = $this->wf->scandir('/var/data'.$directory);
		foreach($d as $v) {
			if($v != '.' && $v != "..") {
				$file = "$dir/$v";
				if(strlen($_directory) > 1)
					$link = "$directory/$v";
				else
					$link = "/$v";
				
				$file = $this->wf->locate_file("/var/data/$link");
				
				$last_mod = date(
					"d M Y / G:i:s", 
					filemtime($file)
				);

				$files[$v] = array(
					'link'     => $this->wf->linker("/data$link"),
					'size'     => $this->wf->bit8_scale(filesize($file)),
					'mimetype' => $this->wf->core_mime()->get_mime($file),
					'last_mod' => $last_mod,
					'realpath' => $file,
					'path'     => $link
				);
			}
		}
	
		/* trie les fichiers */
		ksort($files);
		
		/* crée le template */
		$tpl = new core_tpl($this->wf);
		$tpl->set(
			"dir",
			$directory
		);
		$tpl->set(
			"files",
			$files
		);
		$tpl->set(
			"logo_url",
			$this->wf->linker("/data/logo.png")
		);

		if(strlen($_directory) > 0 && $directory != "/")
			$tpl->set(
				"up_dir",
				$this->wf->linker("/data$up_dir")
			);

		$this->wf->admin_html()->rendering(
			$tpl->fetch("core/data_index")
		);
		exit(0);
	}
	
}

?>