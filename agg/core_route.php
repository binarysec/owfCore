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

class core_route extends wf_agg {
	var $routes = array();
	var $wf;
	var $a_core_cacher;
	
	public function loader($wf) {
		$this->wf = $wf;
		
		/* chargement du module de cache */
		$this->a_core_cacher = $this->wf->core_cacher();
	}

	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Scan routes of modules
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function scan() {
		/* cherche s'il y a des informations dans le cache */
		if(($c = $this->a_core_cacher->get("core_route"))) {
			$this->routes = $c;
			return(TRUE);
		}

		/* parcourt les modules dans le sens initié */
		foreach($this->wf->modules as $info) {
			$actions = $info[8]->get_actions();
			
			/* parcourt et ajoute les actions */
			foreach($actions as $k => $value) {
				$this->parse_and_add_link(
					$k, 
					$info[0],
					$info[1],
					$value
				);
			}
		}
	
		/* cache les données */
		$this->a_core_cacher->store(
			"core_route", 
			$this->routes
		);

		return(TRUE);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Parse and add a link 
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	private function parse_and_add_link($link, $source_dir, $module, $value) {
		$dir = explode("/", $link);

		$nav = &$this->routes;

		for($i=1; $i<count($dir); $i++) {
			if(!$nav[0]) 
				$nav[0] = array();
				
			$nav = &$nav[0][$dir[$i]];
			if(!$nav) {
				$nav = array();
			}
		}
		
		$nav[1] = array_merge(
			array($source_dir, $module), 
			$value
		);
		
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Execute a route without permission checking
	 * accepting channel returned by get_channel
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function execute_route($channel) {
		if($channel[0][2] == WF_ROUTE_ACTION) {
			$filename = $channel[0][0].
				"/route/".
				$channel[0][3].
				".php";
				
			$objectname = 
				"wfr_".
				$channel[0][1]."_".
				preg_replace("/\//", "_", $channel[0][3]);
			
			/* loading concerned object */
			if(!file_exists($filename)) {
				throw new wf_exception(
					$this,
					WF_EXC_PRIVATE,
					"file <strong>$filename".
					"</strong> does not exist"
				);
			}
			require($filename);
			
			/* loading objet */
			$object = new ${objectname}($this->wf);
			$funcname = $channel[0][4];

			/* vérification si la class est bien codé */
			if(get_parent_class($object) != "wf_route_request") {
				throw new wf_exception(
					$this,
					WF_EXC_PRIVATE,
					"object <strong>$objectname".
					"</strong> must be derived ".
					"of wfr_route_request"
				);
			}
			
			/* vérification si la méthode existes */
			if(!method_exists($object, $funcname)) {
				throw new wf_exception(
					$this,
					WF_EXC_PRIVATE,
					"method <strong>$funcname()".
					"</strong> does not exist ".
					"in <strong>".
					get_class($object).
					"</strong> object."
				);
			}

			/* execute la fonction */
			call_user_func(array($object, $funcname));
			exit(0);
		}
		else if($channel[0][2] == WF_ROUTE_REDIRECT) {
			$link = $this->wf->linker($channel[0][3]);
			header("Location: $link");
			exit(0);
		}
		exit(0);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Function used to get channel node
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function get_channel($link) {
		$result = array();
		
		$dir = explode("/", $link);
		$nav = &$this->routes;
		
		for($i=1; $i<count($dir); $i++) {
			if(!$stop && $nav[0][$dir[$i]])
				$nav = &$nav[0][$dir[$i]];
			else
				$stop = TRUE;
			
			
			if($stop == TRUE) {
				$result[1][] = $dir[$i];
				$result[4] .= "/$dir[$i]";
			}
			else
				$result[0] = &$nav[1];
			
			$result[2][] = $dir[$i];
		}
		$result[3] = $link;
		
		return($result);
	}
	
}