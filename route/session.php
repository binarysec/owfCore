<?php

class wfr_core_session extends wf_route_request {
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * constructeur
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function __construct($wf) {
		$this->wf = $wf;

	}

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * fonction de traitement de l'autentification
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function login() {
		echo 'ok';
	}

}

?>