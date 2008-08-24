<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * Web Framework 1                                       *
 * BinarySEC (c) (2000-2008) / www.binarysec.com         *
 * Author: Michael Vergoz <mv@binarysec.com>             *
 * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~         *
 *  Avertissement : ce logiciel est protégé par la       *
 *  loi du copyright et par les traités internationaux.  *
 *  Toute personne ne respectant pas ces dispositions    *
 *  se rendra coupable du délit de contrefaçon et sera   *
 *  passible des sanctions pénales prévues par la loi.   *
 *  Il est notamment strictement interdit de décompiler, *
 *  désassembler ce logiciel ou de procèder à des        *
 *  opération de "reverse engineering".                  *
 *                                                       *
 *  Warning : this software product is protected by      *
 *  copyright law and international copyright treaties   *
 *  as well as other intellectual property laws and      *
 *  treaties. Is is strictly forbidden to reverse        *
 *  engineer, decompile or disassemble this software     *
 *  product.                                             *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

class core_request extends wf_agg {
	var $a_core_session;
	var $a_core_route;
	var $a_core_html;
	var $a_core_request;
	
	public function loader($wf, $position) {
		$this->wf = $wf;

		$this->a_core_session = $this->wf->core_session();
		$this->a_core_route = $this->wf->core_route();
		$this->a_core_html = $this->wf->core_html();
	
	}

	var $channel;
	var $filters;
	var $permissions = array();
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Master request processor
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function process() {
		/* chargement du canal */
		$this->channel = $this->a_core_route->get_channel(
			$_SERVER["PATH_INFO"]
		);

		/* vérification du canal */
		if(!is_object($this->channel[1])) {
			if(count($this->channel[CORE_ROUTE_REQUEST]) == 0) {
				header("Location: ".$this->linker("/index"));
				exit(0);
			}

			$this->a_core_html->set(
				"MESSAGE", 
				"Page not found"
			);
			$this->a_core_html->rendering("html_error_404");
		}
	
		/* vérification si c'est une tentative de login */
		$this->check_for_login();
		
		/* vérification de la session */
		$ret = $this->a_core_session->check_session();
		if($ret != WF_SESSION_VALID) {
			$this->a_core_html->set(
				"LOGIN_MESSAGE", 
				"Session destroyed"
			);
			$this->a_core_html->set(
				"BACK_URL", 
				base64_encode($_SERVER["REQUEST_URI"])
			);
			$this->a_core_html->set(
				"LOGIN_URL", 
				$this->linker("/session/login")
			);
			$this->a_core_html->rendering("html_login");
			exit(0);
		}
		
		/* chargement du canal et des filtres */
		$this->filters = $_GET["f"];
		
		/* vérification des permissions necessaires du canal */
		$perm = unserialize($this->a_core_session->me["permissions"]);
		$need = $this->channel[CORE_ROUTE_MOD][5];

		/* chargement des permissions */
		$this->load_user_permissions(&$perm, &$need);
		
		/* vérification des niveaux de permission */
		if(
			$this->permissions[WF_USER_GOD] ||
			$this->permissions[WF_USER_ADMIN])
			$adm = NULL; // pff
		else if($this->permissions[WF_USER_SIMPLE])
			$this->check_all_permissions(&$need);
		else if($this->permissions[WF_USER_SERVICE])
			$this->check_all_permissions(&$need);
		else
			$this->check_all_permissions(&$need);
		
		/* vérification de la langue */
		$this->wf->core_lang()->check_lang_request();
		
		/* terminaison */
		$this->execute_route(&$this->channel);
	}
	
	private function check_for_login() {
		/* si la route correspond au login */
		if(
			$this->channel[CORE_ROUTE_MOD][1] == "session" &&
			$this->channel[CORE_ROUTE_MOD][2] == "login"
			) {
			$this->execute_route(&$this->channel);
			exit(0);
		}
	}

	private function check_all_permissions($need) {
		foreach($need as $value) {
			if(!$this->permissions[$value]) {
				$this->a_core_html->set(
					"LOGIN_MESSAGE", 
					"You don't have enought of permissions"
				);
				$this->a_core_html->set(
					"BACK_URL", 
					base64_encode($_SERVER["REQUEST_URI"])
				);
				$this->a_core_html->set(
					"LOGIN_URL", 
					$this->linker("/session/login")
				);
				$this->a_core_html->rendering("html_login");
				exit(0);
			}
		}
		return(TRUE);
	}
	
	private function load_user_permissions($perm, $need) {
		foreach($perm as $value)
			$this->permissions[$value] = TRUE;
		return(TRUE);
	}
	
	private function execute_route($channel) {
		/* execution du callback final */
		call_user_func(array(
			$channel[CORE_ROUTE_REQUEST], 
			$this->channel[CORE_ROUTE_MOD][2])
		);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Use to link
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function linker($route, $filter=NULL) {
		if(!$filter) {
			return("/index.php".$route);
		}

	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Use to get input filter array
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function get_filters() {
		return($this->filters);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Use to set output filter by strings
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function set_filter($var, $val) {
		$this->filters[$var] = $val;
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Use to set output filters by an array
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function set_filters($varval = array()) {
		array_merge($this->filters, $varval);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Use to unset filters
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function unset_filters() {
		unset($this->filters);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * fonctions de gestion des headers de la requête
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	var $headers = array();
	public function set_header($key, $val='') {
		$this->headers[$key] = $val;
	}
	
	public function unset_header() {
		unset($this->headers[$key]);
	}
	
	public function send_headers() {
		foreach($this->headers as $k => $v)
			header("$k: $v");
	}
	
	
	
}