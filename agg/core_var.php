<?php


class core_var extends wf_agg {
	public $wf;
	public $struct;
	public $dao;
	public $gcache;
	
	public function loader($wf) {
		$this->wf->core_dao();
		
// 		$this->lang = $this->wf->core_lang()->get_context("tpl/core/smtp/list");
		
		$gc = $this->wf->core_cacher();
		
		$this->gcache = $gc->create_group("core_var");
		
		$this->struct = array(
			"form" => array(
				"perm" => array("core:smtp"),
				
			),
			"data" => array(
				"id" => array(
					"type" => WF_PRI,
				),
				"timeout" => array(
					"type" => WF_INT,
				),
				"timer" => array(
					"type" => WF_INT,
				),
				"key" => array(
					"type" => WF_VARCHAR
				),
				"value" => array(
					"type" => WF_DATA
				),
			),
		);
		
		$this->dao = new core_dao_form_db(
			$this->wf,
			"core_var",
			0,
			$this->struct,
			"core_var",
			"Core SMTP DAO"
		);
	
	}
	
	public function set($key, $data, $timeout=null) {

		if($timeout) {
			$rtimeout = rand(1, $timeout);
			$rtimer = time(); 
		}
		else {
			$rtimeout = 0;
			$rtimer = 0;
		}
		
		$ret = $this->dao->get(array(
			"key" => $key,
		));
		if(count($ret) == 0) {
			$this->dao->add(array(
				"timeout" => $rtimeout,
				"timer" => time(),
				"key" => $key,
				"value" => serialize($data)
			));
			return(true);
		}
		
		$this->dao->modify(
			array(
				"key" => $key,
			),
			array(
				"timeout" => $rtimeout,
				"timer" => time(),
				"key" => $key,
				"value" => serialize($data)
			)
		);
		
		return(true);
	}
	
	public function get($key) {
		
		$ret = $this->dao->get(array(
			"key" => $key,
		));
		if(count($ret) == 0)
			return(null);
		$r = $ret[0];
		
		/* timeout must be checked */
		if($r["timeout"] > 0) {
			$n = time();
			
			if($n-$r["timer"] >= $r["timeout"]) {
				$this->dao->remove(array(
					"key" => $key,
				));
				return(null);
			}
		}
		
		$ret = unserialize($r['data']);
		
		return($ret);
	}
	
	
	
}
