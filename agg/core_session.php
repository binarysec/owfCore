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

define("CORE_SESSION_VALID",        1);
define("CORE_SESSION_TIMEOUT",      2);
define("CORE_SESSION_USER_UNKNOWN", 3);
define("CORE_SESSION_AUTH_FAILED",  4);

class core_session extends wf_agg {
	/** TODO: PARAMETRER */
	var $sess_var = "session";
	var $sess_timeout = 3600;
		
	var $me = NULL;
	var $data = NULL;
	
	public function loader($wf) {
		$this->wf = $wf;
		
		$struct = array(
			"id" => WF_PRI,
			"email" => WF_VARCHAR,
			"password" => WF_VARCHAR,
			"name" => WF_VARCHAR,
			"create_time" => WF_INT,
			"session_id" => WF_VARCHAR,
			"session_time_auth" => WF_INT,
			"session_time" => WF_INT,
			"session_data" => WF_DATA,
			"remote_address" => WF_VARCHAR,
			"permissions" => WF_DATA,
			"data" => WF_DATA
		);
		$this->wf->db->register_zone(
			"core_session", 
			"Core session table", 
			$struct
		);
	
		$this->user_add(array(
			"email" => "mv@binarysec.com",
			"name" => "Michael VERGOZ",
			"password" => "lala",
			"permissions" => array("session:god"),
			"data" => array(),
		));
		
		$this->user_add(array(
			"email" => "td@binarysec.com",
			"name" => "Thomas DIJOUX",
			"password" => "lala",
			"permissions" => array("session:god"),
			"data" => array(),
		));
		
		$this->user_add(array(
			"email" => "op@binarysec.com",
			"name" => "Olivier PASCAL",
			"password" => "lala",
			"permissions" => array("session:god"),
			"data" => array(),
		));
		
		$this->user_add(array(
			"email" => "citron@system.agent",
			"name" => "Michael VERGOZ",
			"password" => "lala",
			"permissions" => array("session:service"),
			"data" => array(),
		));

		$this->user_add(array(
			"email" => "test1@test.test",
			"name" => "Test",
			"password" => "test",
			"permissions" => array("session:service"),
			"data" => array(),
		));

		$this->user_add(array(
			"email" => "test2@test.test",
			"name" => "Test",
			"password" => "test",
			"permissions" => array("session:service"),
			"data" => array(),
		));

		$this->user_add(array(
			"email" => "test3@test.test",
			"name" => "Test",
			"password" => "test",
			"permissions" => array("session:service"),
			"data" => array(),
		));

		$this->user_add(array(
			"email" => "test4@test.test",
			"name" => "Test",
			"password" => "test",
			"permissions" => array("session:service"),
			"data" => array(),
		));

		$this->user_add(array(
			"email" => "test5@test.test",
			"name" => "Test",
			"password" => "test",
			"permissions" => array("session:service"),
			"data" => array(),
		));
		
	}

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Vérification de la session
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function check_session() {
		/* essaye de prendre un numéro de session */
		$session = $_COOKIE[$this->sess_var];

		/* lancement de la recherche */
		$q = new core_db_select("core_session");
		$where = array(
			"session_id" => $session
		);

		$q->where($where);
		$this->wf->db->query($q);
		$res = $q->get_result();

		/* aucune session de disponible */
		if(!$res[0]) {
			if($this->wf->ini_arr["session"]["allow_anonymous"]) {
				$this->me = array(
					"id" => -1,
					"remote_address" => $_SERVER["REMOTE_ADDR"],
					"permissions" => serialize(array(WF_USER_ANON))
				);
				return(CORE_SESSION_VALID);
			}
			else {
				return(CORE_SESSION_TIMEOUT);
			}
		}
		$this->me = $res[0];

		/* vérfication du timeout */
		if(time()-$this->me["session_time"] > $this->sess_timeout) {
			return(CORE_SESSION_TIMEOUT);
		}
			
		/* modification de l'adresse en base + time update */
		$q = new core_db_update("core_session");
		$where = array(
			"id" => $this->me["id"]
		);
		$update = array(
			"remote_address" => $_SERVER["REMOTE_ADDR"],
			"session_time" => time()
		);
		$q->where($where);
		$q->insert($update);
		$this->wf->db->query($q);

		/* chargement des données utilisateur */
		$this->data = unserialize($this->me["session_data"]);
		if(!$this->data)
			$this->data = array();

		return(CORE_SESSION_VALID);
	}
	
	private function generate_session_id() {
		return("E".rand().rand());
	}
	
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Authenfication
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function identify($user, $pass) {
		/* vérification si l'utilisateur existe */
		$q = new core_db_select("core_session");
		$q->where(array(
			"email" => $user,
			"password" => md5($pass)
		));

		$this->wf->db->query($q);
		$res = $q->get_result();

		if(!$res[0]) {
			return(FALSE);
		}
		$this->me = $res[0];

		/* update les informations dans la bdd */
		$q = new core_db_update("core_session");
		$where = array(
			"id" => $this->me["id"]
		);
		$update = array(
			"session_id" => $this->generate_session_id(),
			"session_time_auth" => time(),
			"session_time" => time(),
			"remote_address" => $_SERVER["REMOTE_ADDR"]
		);
		$q->where($where);
		$q->insert($update);
		$this->wf->db->query($q);

		/* utilisation d'un cookie */
		setcookie(
			$this->sess_var,
			$update["session_id"],
			time()+$this->sess_timeout,
			"/"
		);

		/* !! attention redirection necessaire */
		
		return($this->me);
	}

	public function logout() {
		/* essaye de prendre un numéro de session */
		$session = $_COOKIE[$this->sess_var];

		/* lancement de la recherche */
		$q = new core_db_select("core_session");
		$where = array(
			"session_id" => $session
		);

		$q->where($where);
		$this->wf->db->query($q);
		$res = $q->get_result();

		/* aucune session de disponible */
		if(!$res[0])
			return;

		$this->me = $res[0];

		/* modification de l'adresse en base + time update */
		$q = new core_db_update("core_session");
		$where = array(
			"id" => $this->me["id"]
		);
		$update = array(
			"remote_address" => $_SERVER["REMOTE_ADDR"],
			"session_id" => ''
		);
		$q->where($where);
		$q->insert($update);
		$this->wf->db->query($q);

		/* chargement des données utilisateur */
		$this->data = unserialize($this->me["session_data"]);
		if(!$this->data)
			$this->data = array();
	}

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Master request processor
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function user_list($number_of_user=0, $offset=0) {
		$q = new core_db_select("core_session");
		if ($number_of_user)
			$q->limit($number_of_user, $offset);
		$this->wf->db->query($q);
		$res = $q->get_result();
		return($res);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Master request processor
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function user_search_by_mail($mail) {
		$q = new core_db_select("core_session");
		$q->where(array("email" => $mail));
		$this->wf->db->query($q);
		$res = $q->get_result();
		return($res);
		
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Master request processor
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function user_search_by_name($name) {
		$q = new core_db_select("core_session");
		$q->where(array("name" => $name));
		$this->wf->db->query($q);
		$res = $q->get_result();
		return($res);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Master request processor
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function user_info($uid) {
		$q = new core_db_select("core_session");
		$q->where(array("id" => $uid));
		$this->wf->db->query($q);
		$res = $q->get_result();
		if($res)
			return($res[0]);
		return(null);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Master request processor
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function user_mod($uid, $data) {
		/* sanatization */
		if(!$data["email"])
			return(FALSE);

		if(!$data["permissions"])
			$data["permissions"] = array("session:anon");

		/* input */
		$insert = array(
			"email" => $data["email"],
			"name" => $data["name"],
			"permissions" => serialize($data["permissions"]),
		);

		if(array_key_exists('password', $data))
			$insert['password'] = md5($data['password']);
		if(array_key_exists('data', $data))
			$insert['data'] = serialize($data['data']);

		$q = new core_db_update("core_session");
		$where = array("id" => $uid);
		$q->where($where);
		$q->insert($insert);
		$this->wf->db->query($q);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Add new user
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function user_add($data) {
		/* sanatization */
		if(!$data["email"] || !$data["password"])
			return(FALSE);

		if(!$data["permissions"])
			$data["permissions"] = array("session:anon");
		
		/* input */
		$insert = array(
			"email" => $data["email"],
			"name" => $data["name"],
			"password" => md5($data["password"]),
			"create_time" => time(),
			"permissions" => serialize($data["permissions"]),
			"data" => serialize($data["data"])
		);

		/* vérification si l'utilisateur existe */
		if($this->user_search_by_mail($data["email"]))
			return(FALSE);

		/* sinon on ajoute l'utilisateur */
		$q = new core_db_insert("core_session", $insert);
		$this->wf->db->query($q);
		
		return(TRUE);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Master request processor
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function user_del($uid) {
		$q = new core_db_delete("core_session", array("id" => $uid));
		$this->wf->db->query($q);
	}

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Master request processor
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function user_set_permissions($uid, $perms) {
		/* update des perms */
		$q = new core_db_update("core_session");
		$where = array("id" => $uid);
		$update = array("permissions" => serialize($perms));
		$q->where($where);
		$q->insert($update);
		$this->wf->db->query($q);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Master request processor
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function user_add_permissions($uid, $perm) { 
		$perms = $this->user_get_permissions($uid);

		/* l'user n'existe pas */
		if (is_null($perms))
			return NULL;

		/* merge des perms */
		$perms = array_merge($perms, $perm);

		/* supprime les doublons */
		$perms = array_unique($perms);

		/* update des perms */
		$this->user_set_permissions($uid, $perms);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Master request processor
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function user_del_permissions($uid, $perm) { 
		$perms = $this->user_get_permissions($uid);

		/* l'user n'existe pas */
		if (is_null($perms))
			return NULL;

		/* suppression de la permission */
		$perms = array_diff($perms, $perm);

		/* update des perms */
		$this->user_set_permissions($uid, $perms);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Prend les permissions
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function user_get_permissions($uid) { 
		$q = new core_db_select("core_session");
		$q->fields(array("permissions"));
		$q->where(array("id" => $uid));
		$this->wf->db->query($q);
		$res = $q->get_result();
		if (!$res)
			$perms = NULL;
		else
			$perms = unserialize($res[0]["permissions"]);
		return($perms);
	}

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Définition des données utilisateur
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function set_data($data=array()) {
		/* merge les informations */
		if(!$this->data)
			$this->data = $data;
		else
			$this->data = array_merge($this->data, $data);

		/* update les informations dans la bdd */
		$q = new core_db_update("core_session");
		$where = array(
			"id" => $this->me["id"]
		);
		$update = array(
			"session_data" => serialize($this->data)
		);
		$q->where($where);
		$q->insert($update);
		$this->wf->db->query($q);
	}
	
	public function unset_data($list) {
		foreach($list as $v)
			unset($this->data[$v]);
	}
	
	public function get_data($key) {
		return($this->data[$key]);
	}

}