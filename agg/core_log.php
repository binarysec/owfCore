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

define("CORE_LOG_PLAIN",   1);
define("CORE_LOG_MANAGED", 2);

class core_log extends wf_agg {
	public $default = null;
	private $logs = array();
	private $cache;
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * 
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function loader($wf) {
		
		$struct = array(
			"id" => WF_PRI,
			"create_t" => WF_INT,
			"type" => WF_INT,
			"channel" => WF_VARCHAR,
			"next_t" => WF_INT,
		);
		$this->wf->db->register_zone(
			"core_channel", 
			"Core log channel", 
			$struct
		);
		
		$struct = array(
			"id" => WF_PRI,
			"channel_id" => WF_INT,
			"create_t" => WF_INT,
			"size" => WF_INT
		);
		$this->wf->db->register_zone(
			"core_channel_archives", 
			"Core log channel archives", 
			$struct
		);
		
		/* create cache line */
		$this->cache = $this->wf->core_cacher()->create_group(
			"core_log"
		);
		
		$this->default = $this->open_channel(
			"core_log",
			CORE_LOG_MANAGED
		);
		
		
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * 
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function __destruct() {
		foreach($this->logs as $k => $v) {
			/* get channel */
			$res = $this->get("channel", $k);
			if(count($res) <= 0) {
				$k = "core_log";
				$res = $this->default;
			}
			else
				$res = $res[0];

			/* day file */
			$ftime = mktime(0, 0, 0);
			
			/* get filename */
			$filename = $this->wf->get_last_filename(
				"/var/logs/$k/_current/$ftime.log"
			);
			$this->wf->create_dir($filename);

			/* managed buffer */
			if($res["type"] == CORE_LOG_MANAGED)
				$v = $this->manage_data($v);
			
			/* add data to file */
			file_put_contents(
				$filename,
				$v,
				FILE_APPEND
			);
		}
		$this->logs = array();
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * 
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	private function manage_data($data) {
		$ret = NULL;
		$lines = explode("\n", $data);
		foreach($lines as $line) {
			if(strlen($line) > 0)
				$ret .= time().":".$line."\n";
		}
		return($ret);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * 
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function open_channel($name, $type) {
		/* sanatize type */
		if($type != CORE_LOG_PLAIN && $type != CORE_LOG_MANAGED)
			return(NULL);
		
		/* channel */
		$res = $this->get("channel", $name);
		if(!isset($res[0]) || !is_array($res[0])) {
			$insert = array(
				"create_t" => time(),
				"channel" => $name,
				"type" => $type,
				"next_t" => time()+20
			);
			$q = new core_db_insert("core_channel", $insert);
			$this->wf->db->query($q);
			$res = $this->get("channel", $name);
			return isset($res[0]) ? $res[0] : $res;
		}
		
		/* check update */
		if($res[0]["type"] != $type) {
			$q = new core_db_update("core_channel");
			$where = array("id" => $res[0]["id"]);
			$q->where(array("type" => $res[0]["type"]));
			$q->insert($data);
			$this->wf->db->query($q);
		}
		
		return($res[0]);
	}

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * 
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function log($log, $channel=NULL, $async=TRUE) {
		if(!$channel)
			$channel = "core_log";
		if(isset($this->logs[$channel]))
			$this->logs[$channel] .= $log."\n";
		else
			$this->logs[$channel] = $log."\n";
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * 
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function set_archive($channel_id, $time, $size) {
		$res = $this->get("id", $channel_id);
		if(!is_array($res[0]))
			return(false);
		$res = $res[0];
		
		/* get archive */
		$archive = $this->get_archive(array(
			"channel_id" => $channel_id,
			"create_t" => $time,
		));
		if(!is_array($archive[0])) {
			$insert = array(
				"create_t" => (int)$time,
				"channel_id" => (int)$channel_id,
				"size" => (int)$size
			);
			$q = new core_db_insert("core_channel_archives", $insert);
			$this->wf->db->query($q);
		}
		else {
			$q = new core_db_update("core_channel_archives");
			$q->where(array("id" => $archive[0]["id"]));
			$q->insert(array(
				"create_t" => (int)$time,
				"size" => (int)$size
			));
			$this->wf->db->query($q);
		}
		
		$this->wf->log("Log channel rotation MARKER for $res[channel]");
		
		return(true);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * 
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function get($conds=NULL, $extra=NULL) {
		if(is_array($conds))
			$where = $conds;
		else if($extra)
			$where = array($conds => $extra);
		else
			$where = array();
		
		/* create cache line */
		$cl = "g";
		foreach($where as $k => $v)
			$cl .= "_$k:$v";
		
		/* get cache */
		if(($cache = $this->cache->get($cl)))
			return($cache);
			
		/* try query */
		$q = new core_db_select("core_channel");
		$q->where($where);
		$this->wf->db->query($q);
		$res = $q->get_result();

		/* store cache */
		if(isset($res[0]) && is_array($res[0]))
			$this->cache->store($cl, $res);
			
		return($res);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * 
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function get_archive($conds=NULL, $extra=NULL) {
		if(is_array($conds))
			$where = $conds;
		else if($extra)
			$where = array($conds => $extra);
		else
			$where = array();
		
		/* create cache line */
		$cl = "ga";
		foreach($where as $k => $v)
			$cl .= "_$k:$v";
		
		/* get cache */
		if(($cache = $this->cache->get($cl)))
			return($cache);
			
		/* try query */
		$q = new core_db_select("core_channel_archives");
		$q->where($where);
		$this->wf->db->query($q);
		$res = $q->get_result();

		/* store cache */
		if(is_array($res[0]))
			$this->cache->store($cl, $res);
		
		return($res);
	}
	
}

