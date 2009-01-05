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
	private $sess_var;
	private $sess_timeout;
	private $_core_pref;
	
	public $me = NULL;
	var $data = array();
	
	private $pref_session;
	
	public function loader($wf) {
		$this->wf = $wf;
		$this->_core_pref = $this->wf->core_pref();

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
			"remote_hostname" => WF_VARCHAR,
			"forwarded_remote_address" => WF_VARCHAR,
			"forwarded_remote_hostname" => WF_VARCHAR,
			"data" => WF_DATA
		);
		$this->wf->db->register_zone(
			"core_session", 
			"Core session table", 
			$struct
		);
	
		$struct = array(
			"id" => WF_PRI,
			"core_session_id" => WF_INT,
			"perm_name" => WF_VARCHAR,
			"perm_value" => WF_VARCHAR
		);
		$this->wf->db->register_zone(
			"core_session_perm", 
			"Core session permission table", 
			$struct
		);

		$this->user_add(array(
			"email" => "wf@binarysec.com",
			"name" => "Web Framework",
			"password" => "lala",
			"permissions" => array("session:god"),
			"data" => array(),
		));
		
		/* registre session preferences group */
		$this->pref_session = $this->_core_pref->register_group(
			"core_session", 
			"Core session"
		);
		
		/* session variable */
		$this->sess_var = $this->pref_session->register(
			"variable",
			"Variable context",
			CORE_PREF_VARCHAR,
			"session".rand()
		);

		/* session timeout */
		$this->sess_timeout = $this->pref_session->register(
			"timeout",
			"Session timeout",
			CORE_PREF_NUM,
			3600
		);

	}

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Vérification de la session
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function check_session() {
		/* essaye de prendre un numéro de session */
		$session = $_COOKIE[$this->sess_var];

		/* begin: added by keo on 29/11/2008 10:17 */
		/* bug when no session id is found */
		if(!$session) {
			if($this->wf->ini_arr["session"]["allow_anonymous"]) {
				$this->me = array(
					"id" => -1,
					"remote_address" => $_SERVER["REMOTE_ADDR"]
				);
				return(CORE_SESSION_VALID);
			}
			else {
				return(CORE_SESSION_USER_UNKNOWN);
			}
		}
		/* end: added by keo */

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
					"remote_address" => $_SERVER["REMOTE_ADDR"]
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
			"id" => (int)$this->me["id"]
		);
		$update = array(
			"remote_address" => $_SERVER["REMOTE_ADDR"],
			"remote_hostname" => gethostbyaddr($_SERVER["REMOTE_ADDR"]),
			"session_time" => time()
		);
		$q->where($where);
		$q->insert($update);
		$this->wf->db->query($q);

		/* chargement des données utilisateur */
		$this->data = unserialize($this->me["session_data"]);
		if(!$this->data)
			$this->data = array();

		/* merge data & update */
		$this->me = array_merge($this->me, $update);
		
		return(CORE_SESSION_VALID);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Generate a session id
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	private function generate_session_id() {
		$s1 = $this->wf->get_rand();
		$s2 = $this->wf->get_rand();
		return("E".md5($s1).md5($s2));
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
			"id" => (int)$this->me["id"]
		);
		$update = array(
			"session_id" => $this->generate_session_id(),
			"session_time_auth" => time(),
			"session_time" => time(),
			"remote_address" => $_SERVER["REMOTE_ADDR"],
			"remote_hostname" => gethostbyaddr($_SERVER["REMOTE_ADDR"])
		);
		$q->where($where);
		$q->insert($update);
		$this->wf->db->query($q);

		/* merge data & update */
		$this->me = array_merge($this->me, $update);
		
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
			"id" => (int)$this->me["id"]
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
		$cvar = "core_session_user_email_$mail";
		
		$q = new core_db_select("core_session");
		$q->where(array("email" => $mail));
		$this->wf->db->query($q);
		$res = $q->get_result();

		return($res[0]);
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
		$q->where(array("id" => (int)$uid));
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

		/* input */
		$insert = array(
			"email" => $data["email"],
			"name" => $data["name"]
		);

		if($data['password'])
			$insert['password'] = md5($data['password']);
		if(array_key_exists('data', $data))
			$insert['data'] = serialize($data['data']);

		$q = new core_db_update("core_session");
		$where = array("id" => $uid);
		$q->where($where);
		$q->insert($insert);
		$this->wf->db->query($q);
		
		/* ajoute les permissions*/
		$this->user_del_permissions(&$uid);
		if(is_array($data["permissions"])) {
			$this->user_set_permissions(
				&$uid,
				&$data["permissions"]
			);
		}
		return(TRUE);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Add new user
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function user_add($data) {
		/* sanatization */
		if(!$data["email"] || !$data["password"])
			return(FALSE);

		/* vérification si l'utilisateur existe */
		if($this->user_search_by_mail($data["email"]))
			return(FALSE);

		if(count($data["permissions"]) <= 0)
			$data["permissions"] = array("session:simple");
		
		/* input */
		$insert = array(
			"email" => $data["email"],
			"name" => $data["name"],
			"password" => md5($data["password"]),
			"create_time" => time(),
			"data" => serialize($data["data"])
		);

		/* sinon on ajoute l'utilisateur */
		$q = new core_db_insert("core_session", $insert);
		$this->wf->db->query($q);
		$uid = $this->wf->db->get_last_insert_id('core_session_id_seq');

		/* reprend les informations */
		$user = $this->user_search_by_mail($data["email"]);
		
		/* ajoute les permissions*/
		if(is_array($data["permissions"])) {
			$this->user_set_permissions(
				&$user["id"],
				&$data["permissions"]
			);
		}

		/* retourne l'identifiant de l'utisateur créé */
		return($uid);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Master request processor
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function user_del($uid) {
		$user = $this->user_info($uid);

		$q = new core_db_delete(
			"core_session", 
			array("id" => (int)$uid)
		);
		$this->wf->db->query($q);
		
		$q = new core_db_delete(
			"core_session_perm", 
			array("core_session_id" => (int)$uid)
		);
		$this->wf->db->query($q);

		return(TRUE);
	}

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Function used to add a permission
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	private function user_insert_permission($uid, $name, $value=NULL) {
		$insert = array(
			"core_session_id" => (int)$uid,
			"perm_name" => trim($name)
		);
		if($value)
			$insert["perm_value"] = $value;
		
		$q = new core_db_insert("core_session_perm", $insert);
		$this->wf->db->query($q);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Function used to update a permission
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	private function user_update_permission($uid, $name, $value=NULL) {
		$q = new core_db_update("core_session_perm");
		/* begin: added by keo on 05/01/2009 14:25 */
		/* perm_name added in where clause */
		$where = array("core_session_id" => (int)$uid, "perm_name" => $name);
		/* end: added by keo */
		$update = array("perm_name" => trim($name));
		if($value)
			$update["perm_value"] = $value;
			
		$q->where($where);
		$q->insert($update);
		$this->wf->db->query($q);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Master request processor
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function user_set_permissions($uid, $perms, $value=NULL) {
		/* search if uid exists */
		if(!$this->user_info($uid)) 
			return(FALSE);

		/* update data */
		if(is_string($perms)) {
			if(!$this->user_get_permissions(&$uid, &$perms))
				$this->user_insert_permission(
					&$uid, 
					&$perms, 
					&$value
				);
			else
				$this->user_update_permission(
					&$uid, 
					&$perms, 
					&$value
				);
		}
		else if(is_array($perms)) {
			for($a=0; $a<count($perms); $a++) {
				$p = &$perms[$a];
				$v = is_array($value) ? $value[$a] : NULL;
				
				
				if(!$this->user_get_permissions(&$uid, &$p))
					$this->user_insert_permission(
						&$uid, 
						&$p, 
						&$v
					);
				else
					$this->user_update_permission(
						&$uid, 
						&$p, 
						&$v
					);
			}
		}
		else
			return(FALSE);
	
		return(TRUE);
	}
	
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Master request processor
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function user_add_permissions($uid, $perm, $value=NULL) {
		return($this->user_set_permissions(&$uid, &$perm, &$value));
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Master request processor
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	/* added by keo on 05/01/2009 14:41 */
	/* perm parameter optional */
	public function user_del_permissions($uid, $perm=NULL) { 
		if($perm) {
			$q = new core_db_adv_delete();
			
			$q->alias('a', 'core_session');
			$q->alias('b', 'core_session_perm');
			
			$q->do_comp('a.id', '==', (int)$uid);
			$q->do_comp('a.id', '==', 'b.core_session_id');
			$q->do_comp('b.perm_name', '=', $perm);
			
			$this->wf->db->query($q);
		}
		else {
			$q = new core_db_delete(
				'core_session_perm',
				array('core_session_id' => $uid)
			);
			$this->wf->db->query($q);
		}
		return(TRUE);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Get or check permissions.
	 * If $use_and is true then it means that all permissions passed
	 * to $perm will be checked and if not all permissions aren't
	 * verified then the funtion return NULL. If $use_and is false 
	 * then the function will return all available permissions using 
	 * $perms as a mask.
	 * $ask is $perms but arranged with key.
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function user_get_permissions(
			$uid=NULL, 
			$perms=NULL, 
			$use_and=TRUE, 
			$ask=NULL
		) {
		/* use current user ? */
		if(!$uid)
			$uid = $this->me["id"];

		/* begin: added by keo on 05/01/2009 11:00 */
		/* special case for anonymous user */
		if($uid == -1 && (
			is_null($perms) ||
			$perms == 'session:anon' ||
			(!$use_and && $perms['session:anon']) ||
			($use_and && $perms == array('session:anon'))
		)) {
			return(array('session:anon' => TRUE));
		}
		/* end: added by keo */

		if(!is_array($ask))
			$ask = array();

		/* create the request object */
		$q = new core_db_adv_select();

		$q->alias('a', 'core_session');
		$q->alias('b', 'core_session_perm');

		$q->fields("b.perm_name");
		$q->fields("b.perm_value");

		$q->do_comp('a.id', '==', (int)$uid);
		$q->do_comp('a.id', '==', 'b.core_session_id');
		
		/* identifying the request */
		$request = NULL;
		
		/* construct need */
		if(is_string($perms)) {
			$q->do_comp('b.perm_name', '=', $perms);
			$request .= $perms;
		}
		else if(is_array($perms)) {
			/* begin: added by keo on 29/11/2008 11:39 */
			$q->do_open();
			/* end: added by keo */
			for($a=0; $a<count($perms); $a++) {
				$kp = &$perms[$a];
				if($a > 0 && !$use_and)
					$q->do_or();
				$q->do_comp('b.perm_name', '=', $kp);

				$ask[$kp] = TRUE;
				$request .= $kp;
			}
			/* begin: added by keo on 29/11/2008 11:39 */
			$q->do_close();
			/* end: added by keo */
		}

		/* generate the cvar corresponding to the request */
		$cvar = "core_session_preq_$uid".
			"_$request";

		/* execute request */
		$this->wf->db->query($q);
		$res = $q->get_result();

		$tab = array();
		
		/* construct tab perm */
		foreach($res as $lres) {
			$tab[$lres["b.perm_name"]] = $lres["b.perm_value"] == NULL ? 
				TRUE : $lres["b.perm_value"];
		}

		if(count($res) <= 0)
			return(NULL);

		return($tab);
	}

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Check permission and allow GOD and ADMIN if possible
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function check_permissions($need, $uid=NULL, $is=NULL, $need_arranged=NULL) {
		if(!is_array($need_arranged))
			$need_arranged = array();

		/* Get information to if user is privileged */
		$perm = $this->user_get_permissions(
			&$uid,
			&$need,
			TRUE,
			&$need_arranged
		);

		if(!$perm) {
			$is = $this->user_get_permissions(
				&$uid, 
				array(
					WF_USER_GOD,
					WF_USER_ADMIN
				),
				FALSE
			);

			$valid = FALSE;
			if($is[WF_USER_GOD])
				$valid = TRUE;
			else if($is[WF_USER_ADMIN]) {
				if($need_arranged[WF_USER_GOD]) 
					$valid = FALSE;
				else
					$valid = TRUE;
			}
		}
		else
			$valid = TRUE;
		
		return($valid);
	}
	
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Définition des données utilisateur
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function set_data($data) {
		$this->data = array_merge($this->data, $data);

		/* update les informations dans la bdd */
		$q = new core_db_update("core_session");
		$where = array(
			"id" => (int)$this->me["id"]
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
