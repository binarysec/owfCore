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
 
class core_css extends wf_agg {
	var $used = array(); /* données non cachées */
	var $index_cache = array();
	var $wf;
	var $a_core_cacher;
	var $a_core_request;
	var $a_core_html;

	var $group_delimiter = ',';
	
	public function loader($wf) {
		$this->wf = $wf;
		$struct = array(
			"key" => WF_VARCHAR,
			"rand" => WF_VARCHAR,
			"mtime" => WF_TIME,
			"file" => WF_VARCHAR
		);
		$this->wf->db->register_zone(
			"core_css", 
			"Core CSS table", 
			$struct
		);
		
		$this->a_core_cacher = $this->wf->core_cacher();
		$this->a_core_request = $this->wf->core_request();
		$this->a_core_html = $this->wf->core_html();
		
		/* loading cache */
		if(($c = $this->a_core_cacher->get("core_css")) != NULL)
			$this->index_cache = $c;
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Add a css key
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function add($mod, $key) {
		$mod = $this->load($key, $mod);
	}

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Merge requests
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function merge_requests() {
		if(!$this->used)
			return;

		/* merge all keys in one key */
		$merged_key   = '';
		foreach($this->used as $k => $v) $merged_key .= $k.',';
		$merged_key = rtrim($merged_key, ',');

		/* if exist in database, fetch data */
		$data = $this->search($merged_key, TRUE);

		/* else, build the association */
		if(!$data) {
			$merged_files = '';
			$min_mtime    = -1;
			foreach ($this->used as $k => $v) {
				$infos = $this->index_cache[$k];
				$merged_files .= $infos['file'].',';
				if ($min_mtime < 0)
					$min_mtime = $infos['mtime'];
				else if ($infos['mtime'] < $min_mtime)
					$min_mtime = $infos['mtime'];
			}
			$merged_files = rtrim($merged_files, ',');
	
			$data = array(
				"key" => $merged_key,
				"rand" => $this->get_rand(),
				"mtime" => $min_mtime,
				"file" => $merged_files,
			);
	
			/* store in database */
			$q = new core_db_insert("core_css", $data);
			$this->wf->db->query($q);
		}

		return($data);
	}

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Used for rendering css into html code
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function get_css() {
		$data = $this->merge_requests();
		if(!$data)	
			return;

		$request = $this->wf->core_request();
		$final = NULL;

		$key  = $data['key'];
		$rand = $data['rand'];

		$link = $this->wf->linker('/css/'.$rand);
		$final = '<link '.
		         'rel="stylesheet" '.
		         'type="text/css" '.
		         'href="'.$link.'"/>'."\n";
		$this->is_modifiable($k);

		return($final);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Check if the document is modifiable
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	private function is_modifiable($key) {
		/* edition if god */
		if($this->a_core_request->permissions[WF_USER_GOD]) {
			// $this->a_core_html->add_managed_god_body("Modification");
		}

	}

	public function is_cache_outdated($mod) {
		/* test if the cache is outdated */
		$cachemtime = $mod["mtime"];
		$files = $this->unbundle($mod['file']);
		foreach($files as $file){
			if ($cachemtime < filemtime($file))
				return TRUE;
		}

		return FALSE;
	}

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Cached loading
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	private function load($key, $mod=NULL) {
		/* check the cache */
		if($this->index_cache[$key]) {
			$this->used[$key] = TRUE;
			return($this->index_cache[$key]);
		}

		/* fecth the file path */
		$done = FALSE;
		if(isset($this->wf->modules[$mod])) {
			$file = $this->wf->modules[$mod][0].'/var/css/'.$key.'.css';
			$done = file_exists($file);
		}
		
		/* the file doesn't exist */
		if(!$done) {
			throw new wf_exception(
				$this->wf,
				CORE_EXC_PRIVATE,
				"Le fichier ".
				$file.
				" n'existe pas"
			);
			/* generate the creation */
			$this->is_modifiable($key);
			return(NULL);
		}
		
		/* ok we have some information */
		$key = $mod."/".$key;
		$data = array(
			"key" => $key,
			"file" => $file,
		);
		
		/* search CSS in database */
		$q = new core_db_select("core_css");
		$where = array(
			"key" => $key
		);
		$q->where($where);
		$this->wf->db->query($q);
		$res = $q->get_result();
		
		/* unknown CSS stored in database */
		if(!$res[0]) {
			$data["rand"] = $this->get_rand();
			$q = new core_db_insert("core_css", $data);
			$this->wf->db->query($q);
		}
		/* known CSS updatating */
		else {
			$q = new core_db_update("core_css");
			$q->where($where);
			$q->insert($data);
			$this->wf->db->query($q);
		}
		
		/* cache the element */
		$this->used[$key] = TRUE;
		$this->index_cache[$key] = $data;

		/* cache data */
		$this->a_core_cacher->store("core_css", $this->index_cache);
		
		return($data);
	}

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Search data without create it
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	private function search($rand, $by_key=FALSE) {
		/* check the cache */
		if($this->index_cache[$key]) {
			$this->used[$key] = TRUE;
			return($this->index_cache[$key]);
		}
		
		/* check CSS in database */
		$q = new core_db_select("core_css");
		$where = array();
		
		if(!$by_key)
			$where["rand"] = $rand;
		else
			$where["key"] = $rand;

		$q->where($where);
		$this->wf->db->query($q);
		$res = $q->get_result();
		
		if(!$res[0])
			return(FALSE);
		
		return($res[0]);
	}

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Split a set of files
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	private function unbundle($group) {
		if(strpos($group, $this->group_delimiter) !== FALSE)
			return(explode($this->group_delimiter, $group));
		else
			return(array($group));
	}

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Merge a set of files
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	private function merge($files) {
		$group = '';
		foreach ($files as $k => $v) $group .= $k.$this->group_delimiter;
		$group = rtrim($group, $this->group_delimiter);

		return($group);
	}

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Fetch the oldest last modification date of a set of files
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	private function group_mtime($group) {
		$files = $this->unbundle($group);
		$min_mtime = -1;
		foreach($files as $file) {
			$mtime = filemtime($file);
			if($min_mtime < 0 || $mtime > $min_mtime)
				$min_mtime = $mtime;
		}

		return($min_mtime);
	}

	public function minify($data) {
		/* remove comments */
		$data = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $data);
		/* remove tabs, spaces, newlines, etc. */
		$data = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), '', $data);
		return $data;
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Get a file content, cached
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function get_content($key) {
		$mod = $this->search($key);
		if(!$mod) {
			return(FALSE);
		}

		$c = $this->a_core_cacher->get("core_css_$key");
		if($c != NULL && !$this->is_cache_outdated($mod)) {
			$data = $c;
		}
		else {
			/* if the file doesn't exist, we have to display
			   few data because we don't want to generate an
			   error */
			$files = $this->unbundle($mod['file']);
			$data = '';
			foreach($files as $file) {
				if(!file_exists($file)) {
					$data .= "# Content must be edited\n";
				}
				else {
					/* cache content */
					$data .= $this->minify(file_get_contents($file));
				}
			}

			$this->a_core_cacher->store("core_css_$key", $data);

			/* if we have to update the cache */
			if ($this->is_cache_outdated($mod)) {
				$k = $mod["key"];

				/* update metadata */
				$this->index_cache[$k]["mtime"] = $this->group_mtime($mod["file"]);

				/* store in database */
				$q = new core_db_update("core_css");
				$q->where(array("key" => $k));
				$q->insert($this->index_cache[$k]);
				$this->wf->db->query($q);

				/* ...and in cache */
				$this->a_core_cacher->store("core_css", $this->index_cache);
			}
		}

		return($data);
	}

	public function get_last_modified($key) {
		$mod = $this->search($key);
		if(!$mod)
			return(FALSE);

		$mtime = $mod["mtime"];
		$lastmod = date("D, d M Y H:i:s \G\M\T", $mtime);

		return($lastmod);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Ce rand est necessaire car un md5 donne empreinte du nom
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function get_rand() {
		$rot = rand(8, 20);
		for($a=0; $a<$rot; $a++) {
			$who = rand(1, 2);
			if($who == 1)
				$pos = 0x61;
			else
				$pos = 0x41;
			$h = rand(1, 25);
			$r .= chr($h+$pos);
		}
		return($r.".css");
	}
	
}

?>