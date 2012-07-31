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
	public $routes = array();
	public $a_core_cacher;
	
	public function loader($wf) {
		$this->wf = $wf;
	}

	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Scan routes of modules
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function scan() {
		/* parcours les modules dans le sens initié */
		foreach($this->wf->modules as $info) {
			$actions = $info[8]->get_actions();
			
			/* parcours et ajoute les actions */
			if(is_array($actions)) {
				foreach($actions as $k => $value) {
					$this->parse_and_add_link(
						$k, 
						$info[0],
						$info[1],
						$value
					);
				}
			}
		}

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
			if(!isset($nav[0]))
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
			$finfo = $this->wf->locate_file("route/".$channel[0][3].".php", true);
			$filename = $finfo[0];
			$objectname = 
				"wfr_".
				$finfo[2]."_".
				preg_replace("/\//", "_", $channel[0][3]);
			
			/* Old Code
			$filename = $channel[0][0]."/route/".$channel[0][3].".php";
			$objectname = "wfr_".$channel[0][1]."_".preg_replace("/\//", "_", $channel[0][3]);*/
			
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
			$object = new ${'objectname'}($this->wf);
			$funcname = $channel[0][4];

			/* vérification si la classe est bien codée */
			if(get_parent_class($object) != "wf_route_request") {
				throw new wf_exception(
					$this,
					WF_EXC_PRIVATE,
					"object <strong>$objectname".
					"</strong> must be derived ".
					"of wfr_route_request"
				);
			}
			
			/* vérification si la méthode existe */
			if(!method_exists($object, $funcname) && !method_exists($object, "__call")) {
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

			/* exécute la fonction */
			call_user_func(array($object, $funcname));
			exit(0);
		}
		else if($channel[0][2] == WF_ROUTE_REDIRECT) {
			$link = $this->wf->linker($channel[0][3], NULL, NULL, TRUE);
			$this->wf->no_cache();
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
		$lang = $this->wf->core_lang();
		
		$result = array();
		
		$dir = explode("/", $link);
		$nav = &$this->routes;
		$start = 1;
		
		/* checking lang context if available */
		if($lang->check_lang_route(isset($dir[1]) ? $dir[1] : NULL))
			$start++;
		
		for($i=$start; $i<count($dir); $i++) {
			if(!isset($stop) && isset($nav[0][$dir[$i]]))
				$nav = &$nav[0][$dir[$i]];
			else
				$stop = TRUE;

			if(isset($stop) && $stop == TRUE) {
				$result[1][] = $dir[$i];
				if(isset($result[4]))
					$result[4] .= "/$dir[$i]";
				else
					$result[4] = "/$dir[$i]";
			}
			else
				$result[0] = &$nav[1];
			
			$result[2][] = $dir[$i];
		}
		$result[3] = $link;
		
		return($result);
	}
	
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Function used to get channel node
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function get_sub_channel($link) {
		$lang = $this->wf->core_lang();
		
		$result = array();
		
		$dir = explode("/", $link);
		$nav = &$this->routes;
		$start = 1;
		
		/* checking lang context if available */
		if($lang->check_lang_route(isset($dir[1]) ? $dir[1] : NULL))
			$start++;
		for($i=$start; $i<count($dir); $i++) {
			if(isset($nav[0][$dir[$i]]))
				$nav = &$nav[0][$dir[$i]];
			else {
				if(array_key_exists(0, $nav)) {
					foreach($nav[0] as $k => $v) {
						if(array_key_exists(1, $v)) {
						if($v[1][2] == WF_ROUTE_REDIRECT)
							$result[] = array(
								"key" => $k, 
								"name" => $v[1][4],
								"perm" => array_key_exists(6, $v[1]) ? $v[1][6] : null,
								"visible" => $v[1][5],
							);
						else
							$result[] = array(
								"key" => $k, 
								"name" => $v[1][5],
								"perm" => $v[1][7],
								"visible" => $v[1][6],
							);
						}
					}
				}
				break;
			}
		}
	
		return($result);
	}
	
}
