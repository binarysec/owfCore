<?php

class wfr_core_json extends wf_route_request {
	private $request; 
	private $session;
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * constructor
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function __construct($wf) {
		$this->wf = $wf;
		$this->request = $this->wf->core_request();
		$this->session = $this->wf->session();
		
	}

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Handler
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function handler() {
		$agg = trim($this->request->get_argv(0));
		$method = trim($this->request->get_argv(1));
		
		$options = array();
		
		/** \todo Json module scanning could be cached */
		
		/* search json modules */
		$ret = $this->wf->execute_hook("json_module");
		foreach($ret as $modules) {
			foreach($modules as $module) {
				if(!array_key_exists($module["agg"], $options))
					$options[$module["agg"]] = array();
				
				if(count($module["perm"]) == 0)
					$module["perm"] = array("session:admin");
					
				$options[$module["agg"]][$module["method"]] = $module["perm"];
			}
		}
		
		/* check if the aggregator and the method exists */
		if(!array_key_exists($agg, $options))
			$this->return_json(NULL, 1, "Aggregator not permitted");
		if(!array_key_exists($method, $options[$agg]))
			$this->return_json(NULL, 2, "Method not permitted");
		
		$permissions = $options[$agg][$method];
		
		/* search for aggregator */
		$o = $this->wf->__call($agg, false);
		if(!is_object($o))
			$this->return_json(NULL, 3, "Invalid aggregated object");
		
		/* search method */
		if(!method_exists($o, $method))
			$this->return_json(NULL, 4, "Invalid method");
		
		/* run the session checker */
		$this->session->check_session();
		
		/* check permission */
		$ret = $this->session->check_permission($permissions, false);
		if(!$ret) 
			$this->return_json(NULL, 5, "Permission denied");
			
		/* execute the agg method */
		$ret = call_user_func(array($o, $method));
		
		/* send json response */
		$this->return_json($ret);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Use to send JSON response
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	private function return_json($data, $errno=-1, $description=NULL) {
		$ret = array(
			"info" => array(
				"errno" => $errno,
				"description" => $description
			),
			"result" => $data
		);
		
		
		if(isset($this->wf->ini_arr["common"]["access_control_allow_origin"]))
			header("Access-Control-Allow-Origin: ".$this->wf->ini_arr["common"]["access_control_allow_origin"]);
		
		header("Content-Type: text/javascript");
		echo json_encode($ret);
		exit(0);
	}

	
}
