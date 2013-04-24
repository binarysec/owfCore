<?php

abstract class core_dao_octopus {
	
	var $father;
	
	public function __construct($wf) {
		$this->wf = $wf;
		$this->lang = $this->wf->core_lang()->get_context(
			"core/dao/octopus"
		);
		
		$this->load();
	}
	
	public function load() {}
	
	public abstract function get_id();
	public abstract function get_name();
	public abstract function get_description();
	
	public abstract function get_struct();
	public abstract function get_ts_name();
	
	public abstract function search(core_db_adv_select $query);
}
