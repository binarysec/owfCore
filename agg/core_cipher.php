<?php

class core_cipher extends wf_agg {

	public function loader($wf) {
		$this->wf = $wf;

		$this->struct = array(
			"form" => array(
			),
			"data" => array(
				"id" => array(
					"type" => WF_PRI,
				),
				"key" => array(
					"type" => WF_VARCHAR,
				),
				"hash" => array(
					"type" => WF_VARCHAR,
				),
				"value" => array(
					"type" => WF_DATA,
				)
			),
		);
		
		$this->dao = new core_dao_form_db(
			$this->wf,
			"core_cipher",
			NULL,
			$this->struct,
			"core_cipher",
			"Core HTTP security"
		);
		
		$gc = $this->wf->core_cacher();
		
		$this->gcache = $gc->create_group("core_cipher");
		
	}
	
	public function encode($text) {
		$hash = $this->wf->hash($text);
		
		/* get */
		$r = $this->dao->get(array("hash" => $hash));
		if(count($r) == 0) {
		
			/* generate key */
			$level = 8;
			do {
				$k = $this->wf->generate_password($level, true);
				$sr = $this->dao->get(array("key" => $k));
				$level += 2;
			} while(count($sr) != 0);
			
			$i = array(
				"key" => $k,
				"hash" => $hash,
				"value" => $text
			);
			$id = $this->dao->add($i);
			$r = $this->dao->get(array("id" => $id));
		}
		$info = &$r[0];
	
		/* store in cache */
		$this->gcache->store($info["key"], $info);
		
		return($info['key']);
	}
	
	public function decode($key) {
		/* store in cache */
		$r = $this->gcache->get($key);
		if($r)
			return($r["value"]);
			
		$r = $this->dao->get(array("key" => $key));
		if(count($r) == 0) 
			return(false);
		return($r[0]['value']);
	}
	
	public function get_var($varname) {
		$v = $this->wf->get_var("back");
		return($this->decode($v));
	}

}
