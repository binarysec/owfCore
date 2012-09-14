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
	var $_session;
	var $a_core_route;
	var $a_core_html;
	var $a_core_request;
	
	public function loader($wf) {

		$this->_session = $this->wf->session();
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
		
		$this->set_header(
			base64_decode("Q29tcG9zZWQtQnk="),
			base64_decode("T3BlbldG")
		);

		/* Check for real anonymous for action */
		if(isset($this->channel[0]) && $this->channel[0][2] == WF_ROUTE_ACTION)
			$ranon = $this->channel[0][7][0];
		else if(isset($this->channel[0]) && $this->channel[0][2] == WF_ROUTE_REDIRECT)
			$ranon = isset($this->channel[0][6][0]) ? $this->channel[0][6][0] : null;
		else
			$ranon = null;
			
		if($ranon == WF_USER_RANON) {
			$this->_session->check_session();
			$this->wf->execute_hook("core_request_init");
			$this->a_core_route->execute_route($this->channel);
			exit(0);
		}
		
		/* process la session */
		$session_check = $this->_session->check_session();
		
		/* vérification du canal */
		if(!isset($this->channel[0])) {
			if(!$this->channel[3]) {
				$this->wf->no_cache();
				header("Location: ".$this->wf->linker("/"));
				exit(0);
			}
			
			$this->wf->display_error(
				404,
				"Page not found"
			);
			exit(0);
		}
	
		/* vérification si c'est une tentative de login */
		$this->check_for_login();
		
		/* vérification de la session */
		if($session_check == SESSION_TIMEOUT) {
			$this->wf->display_login(
				"Session destroyed",
				null, true
			);
		}

		/* chargement du canal et des filtres */
		$this->filters = NULL;
		if(isset($_GET["f"]))
			$this->filters = $_GET["f"];
		
		/* get channel needed permissions */
		$need = &$this->channel[0][7];
		$need_arranged = array();
		
		/* get uid */
		$uid = isset($this->_session->session_me) ? (int) $this->_session->session_me["id"] : -1 ;

		$display_login = $this->_session->check_permission(
			$need,
			false
		);

		/* do we need to display login ? */
		if(!$display_login) {
			if($uid < 1)
				$this->wf->display_login(
					"You must be connected"
				);
			else
				$this->wf->display_error(403, "You don't have enough permissions");
			
			exit(0);
		}
		
		/* terminate */
		$this->wf->execute_hook("core_request_init");
		$this->a_core_route->execute_route($this->channel);
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
				$this->channel
			);
		}
	}

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Allow to get a request arguments
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function get_argv($pos) {
		return(isset($this->channel[1][$pos]) ? $this->channel[1][$pos] : NULL);
	}
	
	public function get_argc() {
		return(count($this->channel[1]));
	}
	
	public function get_args() {
		return($this->channel[1]);
	}

	public function get_uri() {
		return isset($this->channel[2]) ?
			'/'.implode('/', $this->channel[2]) : '/';
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Allow to get a request ghost argument
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function get_ghost() {
		return(isset($this->channel[4]) ? $this->channel[4] : NULL);
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
	
	public function unset_header($key) {
		unset($this->headers[$key]);
	}
	
	public function send_headers() {
		if(!is_array($this->headers))
			return;
			
		foreach($this->headers as $k => $v) {
			if($v)
				header("$k: $v");
			else
				header($k);
		}
		unset($this->headers);
	}
	
	
	
}
