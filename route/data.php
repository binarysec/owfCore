<?php

class wfr_core_data extends wf_route_request {
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * constructor
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function __construct($wf) {
		$this->wf = $wf;
		$this->a_core_request = $this->wf->core_request();
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
				$allowed = $this->wf->ini_arr["common"]
					["allow_data_index"];
					
				if(!$allowed)
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
		$mime = mime_content_type($used_file);
		if(!$mime)
			$mime = 'application/octet-stream';
		
		/* construit le temps de génération */
		$mtime = filemtime($used_file);
		$file_time = array(
			$mtime,
			date("D, d M Y H:i:s \G\M\T", $mtime)
		);
		
		/* vérifi la requete */
		$requested_time = $_SERVER['HTTP_IF_MODIFIED_SINCE'];
		if($file_time == $requested_time) {
			$this->a_core_request->set_header(
				$_SERVER['SERVER_PROTOCOL']." 304 Not Modified", 
				$file_time
			);
			$this->a_core_request->send_headers();
			exit(0);
		}
		
		/* prepare les type de fichier */
		$this->a_core_request->set_header(
			"Last-modified", 
			$cache_time
		);
		$this->a_core_request->set_header(
			"Content-type", 
			$mime
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
		/* trouve s'il y a un répertoire suppérieur */
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
		
		/* trouve tout le répertoire */
		$dirs = array();
		foreach($this->wf->modules as $mod => $mod_infos) {
			$tmp = $this->wf->modules[$mod][0].
				'/var/data'.$directory;
	
			if(is_dir($tmp)) {
				$dirs[] = $tmp;
				break;
			}
		}
		
		/* recherche les répertoires */
		$files = array();
		foreach($dirs as $dir) {
			$d = scandir($dir);
			foreach($d as $v) {
				if($v != '.' && $v != "..") {
					$file = $dir."/$v";
					if(strlen($_directory) > 1)
						$link = "$directory/$v";
					else
						$link = "/$v";
					
					$last_mod = date("d M Y / G:i:s", filemtime($file));
					
					$files[$v] = array(
						$this->wf->linker("/data$link"),
						$this->wf->bit8_scale(filesize($file)),
						mime_content_type($file),
						$last_mod,
						$file,
					);
				}
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

		echo $tpl->fetch("core_data_index");
		exit(0);
	}
	
}

?>