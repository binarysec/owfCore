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
		
		if(isset($this->args[0])) {
			switch($this->args[0]) {
				case "redirect" : $this->action = WF_ROUTE_REDIRECT; break;
				case "action" : $this->action = WF_ROUTE_ACTION; break;
			}
		}
		
		$this->inception($routes, "");
		
		foreach($this->routes as $route)
			$this->wf->msg($route, true);
		
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
			$this->routes[] = $this->translate($action)." $name  [$dream[0]] ";
	}
	
	private function translate($action) {
		if($action == WF_ROUTE_ACTION)
			return "ACTION  ";
		elseif($action == WF_ROUTE_REDIRECT)
			return "REDIRECT";
		else
			return "ALL";
	}
}