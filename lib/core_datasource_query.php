<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * OpenWF - Open source Web Framework                    *
 * BinarySEC (c) (2000-2009) / www.binarysec.com         *
 * Author: Michael Vergoz <mv@binarysec.com>             *
 * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~         *
 *  Avertissement : ce logiciel est protégé par la       *
 *  loi du copyright et par les traités internationaux.  *
 *  Toute personne ne respectant pas ces dispositions    *
 *  se rendra coupable du délit de contrefaçon et sera   *
 *  passible des sanctions pénales prévues par la loi.   *
 *  Il est notamment strictement interdit de décompiler, *
 *  désassembler ce logiciel ou de procèder à des        *
 *  opération de 'reverse engineering'.                  *
 *                                                       *
 *  Warning : this software product is protected by      *
 *  copyright law and international copyright treaties   *
 *  as well as other intellectual property laws and      *
 *  treaties. Is is strictly forbidden to reverse        *
 *  engineer, decompile or disassemble this software     *
 *  product.                                             *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

class core_datasource_query extends core_datasource {
	private $cache = null;
	
	protected $search_opts = array(
		"cols" => array(),
		"input" => null
	);
	
	public function __construct($wf, $query) {
		parent::__construct($wf, "");
		$this->query = $query;
	}
	
	public function set_search($cols, $input) {
		
		// this will do 'and' but require 'or'
		$this->search_opts["cols"] = $cols;
		$this->search_opts["input"] = $input;
		//foreach($cols as $col)
			//$this->preconds[] = array($col, "~=", $input);
	}
	
	protected function process_search($q) {
		if(count($this->search_opts["cols"]) == 0 || !$this->search_opts["input"])
			return(false);
		$q->do_open();
		foreach($this->search_opts["cols"] as $c) {
			$q->do_comp($c, "~=", "%".$this->search_opts["input"]."%");
			$q->do_or();
		}
		$q->do_close();
		return(true);
	}
	
	public function get_struct() {
		$dbfields = $this->wf->db->get_zone($this->get_name());
		$struct = array();
		foreach($dbfields as $dbfield) {
			$struct[$dbfield['b.name']] = $dbfield['b.type'];
		}
		return($struct);
	}

	public function get_data($conds = array(), $order = array(), $offset = null, $nb = null) {
		$q = clone $this->query;
		foreach($this->preconds as $cond) {
			$q->do_comp($cond[0], $cond[1], $cond[2]);
			$q->do_and();
		}
		foreach($conds as $cond) {
			$q->do_comp($cond[0], $cond[1], $cond[2]);
			$q->do_and();
		}
		$this->process_search($q);
		if($order) {
			$q->order($order);
		}
		if(!is_null($offset) && $nb) {
			$q->limit($nb, $offset);
		}
		$this->wf->db->query($q);
		return($q->get_result());
	}
	
	public function get_options($field) {
		$q = clone $this->query;
		$q->fields("distinct($field)");
		$q->do_comp($field, "!!");
		foreach($this->preconds as $cond) {
			$q->do_comp($cond[0], $cond[1], $cond[2]);
			$q->do_and();
		}
		$this->wf->db->query($q);
		$ret = array();
		foreach($q->get_result() as $res)
			$ret[] = array($field => $res["distinct($field)"]);
		return($ret);
	}

	public function get_num_rows($conds, $ignore_preconds = false) {
		if(!$this->cache)
			$this->cache = $this->wf->core_cacher();
			
		$q = clone $this->query;
		$q->fields = array();
		$q->request_function(WF_REQ_FCT_COUNT);
		
		/* create cache line */
		$cl = "core_ddb_src_".$this->get_name();
		if(!$ignore_preconds) {
			foreach($this->preconds as $cond) {
				$q->do_comp($cond[0], $cond[1], $cond[2]);
				$cl .= "_p$cond[0]$cond[1]$cond[2]";
			}
		}
		foreach($conds as $cond) {
			$q->do_comp($cond[0], $cond[1], $cond[2]);
			$cl .= "_c$cond[0]$cond[1]$cond[2]";
		}
		
		/* manage search counter */
		$r = $this->process_search($q);
		if($r) {
			foreach($this->search_opts["cols"] as $c)
				$cl .= "_s$c";
			if($this->search_opts["input"])
				$cl .= "_i$c";
		}
		
		/* get cache */
		if(($cache = $this->cache->get($cl)))
			return($cache);
			
		$this->wf->db->query($q);
		$res = $q->get_result();
		$count = $res[0]['COUNT(*)'];
		
		/* store cache */
		$this->cache->store($cl, $count);
		
		return($count);
	}

}
