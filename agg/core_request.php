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
	
	public function loader($wf) {
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
			$this->wf->get_rail()
		);
		
		/* Check for real anonymous */
		if($this->channel[0][7][0] == WF_USER_RANON) {
			$this->a_core_route->execute_route(&$this->channel);
			exit(0);
		}
		
		/* vérification du canal */
		if(!$this->channel[0]) {
			if(!$this->channel[3]) {
				header("Location: ".$this->wf->linker("/"));
				exit(0);
			}
			
			$this->wf->display_error(
				404,
				"Page not found"
			);
		}
	
		/* vérification si c'est une tentative de login */
		$this->check_for_login();
		
		/* vérification de la session */
		$ret = $this->a_core_session->check_session();
		if($ret != CORE_SESSION_VALID) {
			$this->wf->display_login(
				"Session destroyed"
			);
		}
		
		/* chargement du canal et des filtres */
		$this->filters = $_GET["f"];
		
		/* get channel needed permissions */
		$need = &$this->channel[0][7];
		$need_arranged = array();
		
		/* get uid */
		$uid = &$this->a_core_session->me["id"];
	
		/* special handle form anon session */
		if($uid == -1) {
			$display_login = FALSE;
			if(is_array($need)) {
				foreach($need as $c) {
					if($c != WF_USER_ANON)
						$display_login = TRUE;
				}
			}
			/* do we need to display login ? */
			if($display_login)
				$this->wf->display_login(
					"You don't have enought of permissions"
				);
		}
		
		$display_login = $this->a_core_session->check_permissions(
			$need, NULL, NULL, &$need_arranged
		);
		
		/* do we need to display login ? */
		if(!$display_login)
			$this->wf->display_login(
				"You don't have enought of permissions"
			);

		/* terminate */
		$this->a_core_route->execute_route(&$this->channel);
	}

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Check if session authentification form is required
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	private function check_for_login() {
		/* si la route correspond au login */
		if(
			$this->channel[2][0] == "session" &&
			$this->channel[2][1] == "login"
			) {
			$this->a_core_route->execute_route(
				&$this->channel
			);
		}
	}

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Allow to get a request arguments
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function get_argv($pos) {
		return($this->channel[1][$pos]);
	}
	
	public function get_argc() {
		return(count($this->channel[1]));
	}
	
	public function get_args() {
		return($this->channel[1]);
	}

	public function get_uri() {
		if($this->channel[2])
			return('/'.implode('/', $this->channel[2]));
		return('/');
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Allow to get a request ghost argument
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function get_ghost() {
		return($this->channel[4]);
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