<?php

class wfr_core_session extends wf_route_request {

	var $a_core_session;
	var $a_core_html;
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * constructeur
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function __construct($wf) {
		$this->wf = $wf;
		$this->a_core_session = $this->wf->core_session();
		$this->a_core_request = $this->wf->core_request();
		$this->a_core_html = $this->wf->core_html();
	}

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * fonction de traitement de l'autentification
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function login() {
		/* prend les inputs */
		$user = $_POST["user"];
		$pass = $_POST["pass"];

		$url = base64_decode($_POST["back_url"]);
		
		if(!$url)
			$this->wf->display_login();
		
		/* vérification de l'utilisateur */
		$ret = $this->a_core_session->identify(
			$user,
			$pass
		);
		/* mot de passe ou mail incorrect */
		if($ret == FALSE) {
			$this->wf->display_login(
				"Wrong email or password"
			);
		}
		/* bon login */
		else {
			if(strlen($url) <= 1) {
				if($this->wf->ini_arr["session"]["default_url"])
					$link = $this->wf->linker($this->wf->ini_arr["session"]["default_url"]);
				else	
					$link = $this->wf->linker('/');
					
				header("Location: ".$link);
				exit(0);
			}
			
			header("Location: ".$url);
			exit(0);
		}
	}

	public function logout() {
		$this->wf->core_session()->logout();
		if($this->wf->ini_arr["session"]["default_url"])
			$link = $this->wf->linker($this->wf->ini_arr["session"]["default_url"]);
		else	
			$link = $this->wf->linker('/');
		header("Location: $link");
		exit(0);
	}

}
