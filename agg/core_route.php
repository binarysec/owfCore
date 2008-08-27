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

define("CORE_ROUTE_ACTION",   1);
define("CORE_ROUTE_REDIRECT", 2);

define("CORE_ROUTE_SHOW", 1); // visible pour l'utilisateur
define("CORE_ROUTE_HIDE", 2); // insivible pour l'utilisateur

define("CORE_ROUTE_MOD",  0);
define("CORE_ROUTE_LINK", 1);

define("CORE_ROUTE_MOD_DEP",   4);
define("CORE_ROUTE_MOD_FILE",  5);

define("_CORE_ROUTE_MOD",     0);
define("_CORE_ROUTE_REQUEST", 1);
define("_CORE_ROUTE_UNKNOWN", 2);

class core_route extends wf_agg {
	var $routes = array();
	var $wf;
	var $a_core_cacher;
	var $a_core_request;
	
	public function loader($wf, $position) {
		$this->wf = $wf;
		
		/* chargement du module de cache */
		$this->a_core_cacher = $this->wf->core_cacher();
		$this->a_core_request = $this->wf->core_request();
	
		/* initialise les routes */
		if(!$this->routes[CORE_ROUTE_MOD])
			$this->routes[CORE_ROUTE_MOD] = array();
			
		if(!$this->routes[CORE_ROUTE_LINK])
			$this->routes[CORE_ROUTE_LINK] = array();
	}

	public function scan() {
		/* cherche s'il y a des informations dans le cache */
		if(($c = $this->a_core_cacher->get("wfa_core_route")) != NULL) {
			$this->routes = $c;
			return(TRUE);
		}

		/* parcourt les modules dans le sens initié */
		foreach($this->wf->modules as $info) {
			$sub = $info[0]."/route";
			$file = $sub."/_info.php";

			if(file_exists($file)) {
				$this->load_info_file(
					$file, 
					$info[1]
				);
			}
		}
	
		/* cache les données */
		$this->a_core_cacher->store("wfa_core_route", $this->routes);
		
		return(TRUE);
	}
	
	private function load_info_file($file, $mod) {
		/* charge le fichier */
		require($file);
		
		/* defini le nom de l'objet */
		$objectname = "_wfr_".$mod;
		
		/* charge l'objet */
		$obj = new ${objectname}($this->wf);
	
		/* sanatize */
		if(get_parent_class($obj) != "wf_route_info") {
			throw new wf_exception(
				$this->wf,
				CORE_EXC_PRIVATE,
				"<b>$objectname</b>".
				" must be derived from <u>wf_route_info</u>, ".
				"check your code."
			);
		}
		
		/* recupere les informations du modules de routage */
		if($this->routes[CORE_ROUTE_MOD][$mod])
			return(TRUE);
			
		$this->routes[CORE_ROUTE_MOD][$mod] = $obj->get_info();
		$this->routes[CORE_ROUTE_MOD][$mod][CORE_ROUTE_MOD_DEP] = $objectname;
		$this->routes[CORE_ROUTE_MOD][$mod][CORE_ROUTE_MOD_FILE] = $file;


		/* prend les actions */
		$actions = $obj->get_actions();
		
		/* parcourt les actions */
		foreach($actions as $link => $value) {
			$this->parse_link($link, $value, $mod);
		}
		return(TRUE);
	}
	

	private function parse_link($link, $value, $mod) {
		$dir = explode("/", $link);

		$nav = &$this->routes[CORE_ROUTE_LINK];
		for($i=1; $i<count($dir); $i++) {
			$nav = &$nav[$dir[$i]];
			if(!$nav)
				$nav = array($dir[$i]);
		}
		$nav[1] = $value;
		$nav[1][] = $link;
		$nav[1][] = $mod;
	}
	
	public function get_channel($link) {
		$result = array(
			_CORE_ROUTE_MOD => array(),
			_CORE_ROUTE_UNKNOWN => array()
		);
		$dir = explode("/", $link);
		$nav = &$this->routes[CORE_ROUTE_LINK];
		$stop = FALSE;
		for($i=1; $i<count($dir); $i++) {
			if($nav[$dir[$i]])
				$nav = &$nav[$dir[$i]];
			else
				$stop = TRUE;

			if($stop == TRUE) {
				$result[_CORE_ROUTE_UNKNOWN][] = $dir[$i];
				$stop = TRUE;
			}
			else
				$result[_CORE_ROUTE_MOD] = &$nav[1];
		}

		/* si un resultat est trouvé alors on charge la requête en question */
		if($result[_CORE_ROUTE_MOD]) {
			/* si le module est une redirection */
			if($result[_CORE_ROUTE_MOD][0] == CORE_ROUTE_REDIRECT) {
				header(
					"Location: ".
					$this->wf->linker(
						$result[_CORE_ROUTE_MOD][1]
					)
				);
				exit(0);
			}

			/* recupere les informations du modules qui gere l'action */
			$mod = &$this->routes[CORE_ROUTE_MOD][$result[_CORE_ROUTE_MOD][1]];

			/* search in modules list to find the good directory */
			$md = $this->wf->modules[$result[_CORE_ROUTE_MOD][7]];			
			$obj_file = "$md[0]/route/".
				$result[_CORE_ROUTE_MOD][1].
				".php";
		
			/* chargement du fichier concerné */
			if(!file_exists($obj_file)) {
				throw new wf_exception(
					$this,
					CORE_EXC_PRIVATE,
					"file: <u>$obj_file</u> does not exist"
				);
			}
			require($obj_file);
			
			/* défini le nom du fichier de requête */
			$objname = "wfr_".
				$result[_CORE_ROUTE_MOD][7].
				"_".
				$result[_CORE_ROUTE_MOD][1];
				
			/* création et stockage de l'objet */
			$result[_CORE_ROUTE_REQUEST] = new ${objname}($this->wf);
			
			/* prend nom de la fonction */
			$funcname = $result[_CORE_ROUTE_MOD][2];
			
			/* vérification si la méthode existes */
			if(!method_exists($result[_CORE_ROUTE_REQUEST], $funcname)) {
				throw new wf_exception(
					$this,
					CORE_EXC_PRIVATE,
					"method <b>$funcname()</b> does not exist in <b>".
					get_class($result[_CORE_ROUTE_REQUEST]).
					"</b> object."
				);
			}
		}

		return($result);
	}
	
	
	
}