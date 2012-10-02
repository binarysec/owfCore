<?php

if(!defined("OWFCONSOLE"))
	die("This script should be ran using owfconsole.php");

class core_routes extends wf_cli_command {
	private $routes;
	private $action;
	
	public function process() {
		$routes = $this->wf->core_route()->routes;
		$this->routes = array();
		$this->action = 0;
		
		$filter = isset($this->args[0]) ? $this->args[0] : "/";
		
		if($this->wf->get_opt("r") && !$this->wf->get_opt("a"))
			$this->action = WF_ROUTE_REDIRECT;
		elseif($this->wf->get_opt("a") && !$this->wf->get_opt("r"))
			$this->action = WF_ROUTE_ACTION;
		
		$this->inception($routes, "");
		
		foreach($this->routes as $route)
			//var_dump(substr($route, 0, strlen($filter)));
			if(substr($route["route"], 0, strlen($filter)) == $filter)
				$this->wf->msg(
					"$route[action] $route[route] [$route[perm]]",
					true
				);
		
		return true;
	}
	
	private function inception(array $dream, $name, $action = 0) {
		$wokeup = true;
		
		foreach($dream as $k => $innerdream)
			if(is_array($innerdream)) {
				$this->inception($innerdream, is_string($k) ? "$name/$k" : $name, isset($dream[2]) ? $dream[2] : 0);
				$wokeup = false;
			}
			elseif(is_string($k))
				$name .= "/$k";
		
		if($wokeup && !empty($name) && (!$this->action || $this->action == $action))
			$this->routes[] = array(
				"action" => $this->translate($action),
				"route" => $name,
				"perm" => $dream[0],
			);
	}
	
	private function translate($action) {
		if($action == WF_ROUTE_ACTION)
			return "ACTION  ";
		elseif($action == WF_ROUTE_REDIRECT)
			return "REDIRECT";
		else
			return "ALL     ";
	}
}