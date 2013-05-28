<?php

if(!defined("OWFCONSOLE"))
	die("This script should be ran using owfconsole.php");

class core_preferences extends wf_cli_command {
	private $routes;
	private $action;
	
	public function help() {
		$this->wf->msg("Usage: core routes [filter] [-a] [-r]");
		$this->wf->msg("Sample: core routes / -r");
	}
	
	public function process() {
		$group = isset($this->args[0]) ? $this->args[0] : "";
		
		$pref_groups = $this->wf->core_pref()->group_find();
		
		/* if specified a group, lookup if can find it */
		if(!empty($group))
			foreach($pref_groups as $pref_group)
				if(strtolower($group) == strtolower($pref_group["name"]))
					return $this->display_group($pref_group);
		
		/* otherwise, display groups */
		foreach($pref_groups as $pref_group) {
			$description = !empty($pref_group["description"]) ?
				"(".base64_decode($pref_group["description"]).") " : "";
			$this->wf->msg("$pref_group[name] $description- created on ".date(DATE_COOKIE, $pref_group["create_time"]));
		}
		
		return true;
	}
	
	private function display_group($pref_group) {
		$pref_context = $this->wf->core_pref()->register_group($pref_group["name"]);
		$variables = $pref_context->get_all();
		
		$this->wf->msg("---------- VARIABLES OF GROUP $pref_group[name] :");
		foreach($variables as $var) {
			$description = !empty($var["description"]) ?
				"(".base64_decode($var["description"]).") " : "";
			$this->wf->msg("$var[variable] $description");
			$this->wf->msg(" |-> value is $var[value] (default $var[dft])");
		}
		
		return true;
	}
}