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
	
	var $a_core_cacher;
	var $a_core_request;
	var $a_core_html;

	var $group_delimiter = ',';
	
	public function loader($wf, $position) {
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
		
		/* chargement du cache */
		if(($c = $this->a_core_cacher->get("wfa_core_css")) != NULL)
			$this->index_cache = $c;
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Ajoute une clé CSS
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function add($mod, $key) {
		//~ trigger_error('ajout de <<'.$mod.'|'.$key.'>> à la page', WF_E_NOTICE);
		$mod = $this->load($key, $mod);
	}

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Groupe les requêtes
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function merge_requests() {
		if(!$this->used)
			return;

		/* on fusionne les clés en une seule clé */
		$merged_key   = '';
		foreach($this->used as $k => $v) $merged_key .= $k.',';
		$merged_key = rtrim($merged_key, ',');

		/* si l'association existe déjà en bdd,
		   on récupère les données */
		$data = $this->search($merged_key, TRUE);

		/* sinon, on construit l'association */
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
	
			/* et on l'enregistre en bdd */
			$q = new wf_db_insert("core_css", $data);
			$this->wf->db->query($q);
		}

		return($data);
	}

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Utilisé pour le rendement du CSS dans du code HTML
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function get_css() {
		$data = $this->merge_requests();
		if(!$data)	
			return;

		$request = $this->wf->core_request();
		$final = NULL;

		$request = $this->wf->core_request();

		$key  = $data['key'];
		$rand = $data['rand'];

		$link = $request->linker('/css/'.$rand);
		$final = '<link '.
		         'rel="stylesheet" '.
		         'type="text/css" '.
		         'href="'.$link.'"/>'."\n";
		$this->is_modifiable($k);

		return($final);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Vérification si le document est modifiable
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	private function is_modifiable($key) {
		/* proposition d'édition si phénome est un god ?! */
		if($this->a_core_request->permissions[WF_USER_GOD]) {
			$this->a_core_html->add_managed_god_body("Modification");
		}

	}

	public function is_cache_outdated($mod) {
		/* test si le cache est périmé */
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
	 * Chargement caché
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	private function load($key, $mod=NULL) {
		/* check le cache */
		if($this->index_cache[$key]) {
			//~ trigger_error('lecture de la clé dans la SHM', WF_E_NOTICE);
			$this->used[$key] = TRUE;
			return($this->index_cache[$key]);
		}

		/* récupère le chemin du fichier */
		$done = FALSE;
		if(isset($this->wf->modules[$mod])) {
			$file = $this->wf->modules[$mod][0].'/var/css/'.$key.'.css';
			$done = file_exists($file);
		}
		
		/* le fichier n'existe pas */
		if(!$done) {
			//~ trigger_error('le fichier <<'.$file.'>> n\'existe pas', WF_E_WARNING);
			/* generer la création ??? */
			$this->is_modifiable($key);
			return(NULL);
		}
		
		/* ok on a des informations */
		$key = $mod."/".$key;
		$data = array(
			"key" => $key,
			"file" => $file,
		);
		
		/* cherche si le CSS existe en base */
		$q = new core_db_select("core_css");
		$where = array(
			"key" => $key
		);
		$q->where($where);
		$this->wf->db->query($q);
		$res = $q->get_result();
		
		/* CSS inconnu mise en base */
		if(!$res[0]) {
			//~ trigger_error('insertion des informations en BDD', WF_E_NOTICE);
			$data["rand"] = $this->get_rand();
			$q = new wf_db_insert("core_css", $data);
			$this->wf->db->query($q);
		}
		/* CSS connu updatating */
		else {
			//~ trigger_error('MAJ des informations en BDD', WF_E_NOTICE);
			$q = new wf_db_update("core_css");
			$q->where($where);
			$q->insert($data);
			$this->wf->db->query($q);
		}
		
		/* cache l'element */
		$this->used[$key] = TRUE;
		$this->index_cache[$key] = $data;

		/* cache les données */
		$this->a_core_cacher->store("wfa_core_css", $this->index_cache);
		
		return($data);
	}

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Cherche la données sans la créer
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	private function search($rand, $by_key=FALSE) {
		/* cherche dans le cache */
		if($this->index_cache[$key]) {
			//~ trigger_error('lecture des informations dans la SHM', WF_E_NOTICE);
			$this->used[$key] = TRUE;
			return($this->index_cache[$key]);
		}
		
		/* cherche si le CSS existe en base */
		//~ trigger_error('lecture des informations en BDD', WF_E_NOTICE);
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
	 * Découpe un groupe de fichiers
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	private function unbundle($group) {
		if(strpos($group, $this->group_delimiter) !== FALSE)
			return(explode($this->group_delimiter, $group));
		else
			return(array($group));
	}

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Fusionne un groupe de fichiers
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	private function merge($files) {
		$group = '';
		foreach ($files as $k => $v) $group .= $k.$this->group_delimiter;
		$group = rtrim($group, $this->group_delimiter);

		return($group);
	}

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Récupère la date de dernière modification la plus ancienne
	 * d'un groupe de fichiers
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
	 * Prend le contenu d'un fichier en mode caché
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function get_content($key) {
		$mod = $this->search($key);
		if(!$mod) {
			//~ trigger_error('clé <<'.$key.'>> introuvable en SHM ou en BDD', WF_E_WARNING);
			return(FALSE);
		}

		$c = $this->a_core_cacher->get("core_css_$key");
		if($c != NULL && !$this->is_cache_outdated($mod)) {
			//~ trigger_error('cache à jour, lecture des données en SHM', WF_E_NOTICE);
			$data = $c;
		}
		else {
			/* si le fichier n'existe pas alors il faut 
			   simplement afficher quelques données statiques
			   pour ne pas générer d'erreur. */
			$files = $this->unbundle($mod['file']);
			$data = '';
			foreach($files as $file) {
				if(!file_exists($file)) {
					//~ trigger_error('le fichier <<'.$file.'>> n\'existe pas', WF_E_WARNING);
					$data .= "# Content must be edited\n";
				}
				else {
					//~ trigger_error('lecture du contenu et mise en cache en SHM', WF_E_NOTICE);
					/* cache le contenu */
					$data .= $this->minify(file_get_contents($file));
				}
			}

			$this->a_core_cacher->store("core_css_$key", $data);

			/* si on doit mettre à jour le cache */
			if ($this->is_cache_outdated($mod)) {
				//~ trigger_error('cache invalide, MAJ des informations en SHM et BDD', WF_E_NOTICE);
				$k = $mod["key"];

				/* met à jour les métadonnées... */
				$this->index_cache[$k]["mtime"] = $this->group_mtime($mod["file"]);

				/* ...dans la base... */
				$q = new wf_db_update("core_css");
				$q->where(array("key" => $k));
				$q->insert($this->index_cache[$k]);
				$this->wf->db->query($q);

				/* ...et dans le cache */
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