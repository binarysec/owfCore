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
		$this->a_session = $this->wf->session();
		
		$this->lang = $this->wf->core_lang()->get_context(
			"core/data_index"
		);
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
					"Document does not exists"
				);
			}
			else {
				/* Allow data index */
				if(!$this->wf->ini_arr["common"]["allow_data_index"]) {
					$this->wf->display_error(
						403,
						"Data index listing is disabled"
					);
					return(TRUE);
				}
				
				if($this->a_session->session_me["id"] == -1) {
					$this->wf->display_error(
						403,
						"You should be authenticated"
					);
					return(TRUE);
				}
				
				/* autorisé à afficher listing ? */
				if(
					!isset($this->a_session->session_my_perms["session:god"]) &&
					!isset($this->a_session->session_my_perms["session:admin"])
					) {
					$this->wf->display_error(
						403,
						"No permissions for index listing"
					);
					return(TRUE);
				}
		
				if(!$this->wf->mod_exists("admin"))
					$this->wf->display_error(
						404,
						"Need OpenWF admin module"
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
		if(!isset($used_file))
			return(FALSE);
		
		/* prend le type de contenu */
		$mime = $this->wf->core_mime()->get_mime(
			$used_file
		);
		
		/* construit le temps de génération */
		$mtime = filemtime($used_file);
		$file_time = date("D, d M Y H:i:s \G\M\T", $mtime);
		
		/* vérifie la requete */
		$requested_time = NULL;
		if(isset($_SERVER['HTTP_IF_MODIFIED_SINCE']))
			$requested_time = $_SERVER['HTTP_IF_MODIFIED_SINCE'];
		
		if($file_time == $requested_time) {
			$this->a_core_request->set_header(
				$_SERVER['SERVER_PROTOCOL'].
				" 304 Not Modified"
			);
			$this->a_core_request->set_header(
				"Cache-Control",
				"max-age=3600"
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
		
		$this->a_core_request->unset_header(
			"Set-Cookie"
		);

		/* Cache control by expires */
// 		$mtime = time()+3600;
// 		$expires = date("D, d M Y H:i:s \G\M\T", $mtime);
// 		$this->a_core_request->set_header(
// 			"Expires",
// 			$expires
// 		);
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
		if(!isset($used_file))
			return(FALSE);
		return(TRUE);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Draw the listing
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	private function show_listing($_directory) {
		$ah = $this->wf->admin_html();
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
				if(strlen($_directory) > 1)
					$link = "$directory/$v";
				else
					$link = "/$v";
				
				$file = $this->wf->locate_file("/var/data/$link");

				$files[$v] = array(
					'link'     => $this->wf->linker("/data$link"),
					'size'     => $this->wf->bit8_scale(filesize($file)),
					'mimetype' => $this->wf->core_mime()->get_mime($file),
					'lastmod'  => filemtime($file),
					'realpath' => $file,
					'path'     => $link,
					'is_file'  => is_file($file)
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

		$up_dir_link = (strlen($_directory) > 0 && $directory != "/") ?
				$this->wf->linker("/data$up_dir") :
				"";
				
		$tpl->set(
			"up_dir",
			$up_dir_link
		);

		$ah->set_title($this->lang->ts("Directory index"));
		if(strlen($up_dir_link) > 0)
			$ah->set_backlink($up_dir_link);
		else
			$ah->set_backlink($this->wf->linker('/admin/system'), "Home", "home");
			
		$ah->rendering(
			$tpl->fetch("core/data_index")
		);
		exit(0);
	}
	
}
