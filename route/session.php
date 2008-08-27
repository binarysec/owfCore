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
		$url = base64_decode($_POST["url"]);
		
		/* vérification de l'utilisateur */
		$ret = $this->a_core_session->identify(
			$user,
			$pass
		);
		/* mot de passe ou mail incorrect */
		if($ret == FALSE) {
			$this->a_core_html->set(
				"message", 
				"Wrong email or password"
			);
			$this->a_core_html->set(
				"back_url", 
				base64_encode($url)
			);
			$this->a_core_html->set(
				"login_url", 
				$this->a_core_request->linker("/session/login")
			);
			$this->a_core_html->rendering('core_login');
			exit(0);
		}
		/* bon login */
		else {
			header("Location: ".$url);
			exit(0);
		}
	}

}
 
?>