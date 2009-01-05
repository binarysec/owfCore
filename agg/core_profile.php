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
 *  opération de 'reverse engineering'.                  *
 *                                                       *
 *  Warning : this software product is protected by      *
 *  copyright law and international copyright treaties   *
 *  as well as other intellectual property laws and      *
 *  treaties. Is is strictly forbidden to reverse        *
 *  engineer, decompile or disassemble this software     *
 *  product.                                             *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

define('CORE_PROFILE_NUM',     900);
define('CORE_PROFILE_BOOL',    902);
define('CORE_PROFILE_VARCHAR', 903);
define('CORE_PROFILE_DATA',    904);
define('CORE_PROFILE_SELECT',  905);

class core_profile_context {
	public $wf;

	public $id;
	public $create_time;
	public $name;
	public $description;
	
	private $fields = array();
	private $values = array();
	private $need_up = FALSE;
	
	public function loader($wf) {
		$this->wf = $wf;

		$struct = array(
			'id'          => WF_PRI,
			'create_time' => WF_INT,
			'name'        => WF_VARCHAR,
			'description' => WF_VARCHAR,
			'perms'       => WF_VARCHAR
		);
		$this->wf->db->register_zone(
			'core_profile',
			'Profile',
			$struct
		);
		
		$struct = array(
			'id'          => WF_PRI,
			'create_time' => WF_INT,
			'field'       => WF_VARCHAR,
			'description' => WF_VARCHAR,
			'profile_id'  => WF_INT,
			'type'        => WF_INT,
			'dft'         => WF_DATA,
			'serial'      => WF_DATA
		);
		$this->wf->db->register_zone(
			'core_profile_field',
			'Profile fields',
			$struct
		);

		$struct = array(
			'id'         => WF_PRI,
			'field'      => WF_VARCHAR,
			'profile_id' => WF_INT,
			'user_id'    => WF_INT,
			'value'      => WF_DATA
		);
		$this->wf->db->register_zone(
			'core_profile_value',
			'Profile values',
			$struct
		);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * When destruction update the cache if necessary
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function __destruct() {
		if($this->need_up) {
			$this->need_up = FALSE;
			$this->wf->core_profile()->store_context(
				$this, $this->name
			);
		}
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Register a new field 
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function register($field, $desc, $type, $dft, $serial=NULL) {
		if($this->fields[$field])
			return(true);
			
		$data = $this->db_search_field($field);
		if(!$data) {
			$insert = array(
				'create_time' => time(),
				'field'       => $field,
				'description' => base64_encode($desc),
				'profile_id'  => $this->id
			);

			switch($type) {
				case CORE_PROFILE_NUM:
					$insert['type'] = CORE_PROFILE_NUM;
					$insert['dft']  = $dft;
					break;
					
				case CORE_PROFILE_BOOL:
					$insert['type'] = CORE_PROFILE_BOOL;
					$insert['dft']  = $dft;
					break;
					
				case CORE_PROFILE_VARCHAR:
					$insert['type'] = CORE_PROFILE_VARCHAR;
					$insert['dft']  = $dft;
					break;
				
				case CORE_PROFILE_DATA:
					$insert['type'] = CORE_PROFILE_DATA;
					$insert['dft']  = $dft;
					break;
				
				default:
					throw new wf_exception(
						$this,
						WF_EXC_PRIVATE,
						'Preference type unknown for '.
						$this->name.'::'.$field
					);
			}

			$q = new core_db_insert('core_profile_field', $insert);
			$this->wf->db->query($q);
			
			$this->fields[$field] = $insert;

			/* need cacher update */
			$this->need_up = TRUE;
		}
		else if($desc != $data['description']) {
			$q = new core_db_update('core_profile_field');
			$where = array(
				'field'       => $field,
				'profile_id'  => $this->id
			);

			$insert = array(
				'description' => base64_encode($desc)
			);

			$q->where($where);
			$q->insert($insert);
			$this->wf->db->query($q);

			if(!is_array($this->fields[$field]))
				$this->fields[$field] = array();

			$this->fields[$field] = array_merge(
				&$data,
				&$insert
			);

 			/* need cacher update */
 			$this->need_up = TRUE;
		}
		
		return(true);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Change the value of a field 
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function set_value($field, $uid, $value) {
		$ret = $this->db_search_value($field, $uid);

		$where = array(
			'field'      => $field,
			'profile_id' => $this->id,
			'user_id'    => $uid
		);

		/* update database */
		if($ret) {
			$q = new core_db_update('core_profile_value');
			$q->where($where);
			$q->insert(array('value' => $value));
		}
		else {
			$q = new core_db_insert(
				'core_profile_value',
				array_merge($where, array('value' => $value))
			);
		}
		$this->wf->db->query($q);
	
		/* update short cache */
		$this->values[$field][$uid]['value'] = $value;
		
		/* need cacher update */
		$this->need_up = TRUE;
		
		return(TRUE);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Get a value
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function get_value($field, $uid) {
		$ret = $this->db_search_value($field, $uid);
		return($ret['value']);
	}

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Get the default value
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function get_default($field, $uid) {
		$ret = $this->db_search_value($field, $uid);
		return($ret['dft']);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Get/load all fields
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function get_all_fields() {
		$q = new core_db_select('core_profile_field');
		$q->where(array('profile_id' => $this->id));
		$this->wf->db->query($q);
		$res = $q->get_result();

		foreach($res as $info) {
			$this->fields[$info['field']] = $info;
		}

		/* need cacher update */
		$this->need_up = TRUE;
		
		return($this->fields);
	}

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Low level function to search a field
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	private function db_search_field($field) {
		if(is_array($this->fields[$field]))
			return($this->fields[$field]);
			
		$q = new core_db_select('core_profile_field');
		$q->where(array(
			'field' => $field,
			'profile_id' => $this->id
		));
		$this->wf->db->query($q);
		$res = $q->get_result();

		/* store short cache */
		$this->fields[$field] = $res[0];

		/* need cacher update */
		$this->need_up = TRUE;

		return($res[0]);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Low level function to search a value
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	private function db_search_value($field, $uid) {
		if(is_array($this->values[$field][$uid]))
			return($this->values[$field][$uid]);

		$q = new core_db_select('core_profile_value');
		$q->where(array(
			'field' => $field,
			'profile_id' => $this->id,
			'user_id' => $uid
		));
		$this->wf->db->query($q);
		$res = $q->get_result();

		/* store short cache */
		$this->values[$field][$uid] = $res[0];

		/* need cacher update */
		$this->need_up = TRUE;

		return($res[0]);
	}
}

class core_profile extends wf_agg {
	private $_core_cacher;
	
	public function loader($wf) {
		$this->wf = $wf;

		$this->_core_cacher = $wf->core_cacher();
		
		$struct = array(
			'id'          => WF_PRI,
			'create_time' => WF_INT,
			'name'        => WF_VARCHAR,
			'description' => WF_VARCHAR,
			'perms'       => WF_VARCHAR
		);
		$this->wf->db->register_zone(
			'core_profile',
			'Profile',
			$struct
		);
		
		$struct = array(
			'id'          => WF_PRI,
			'create_time' => WF_INT,
			'field'       => WF_VARCHAR,
			'description' => WF_VARCHAR,
			'profile_id'  => WF_INT,
			'type'        => WF_INT,
			'dft'         => WF_DATA,
			'serial'      => WF_DATA
		);
		$this->wf->db->register_zone(
			'core_profile_field',
			'Profile fields',
			$struct
		);

		$struct = array(
			'id'         => WF_PRI,
			'field'      => WF_VARCHAR,
			'profile_id' => WF_INT,
			'user_id'    => WF_INT,
			'value'      => WF_DATA
		);
		$this->wf->db->register_zone(
			'core_profile_value',
			'Profile values',
			$struct
		);
	}
	
	private $contexts = array();
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Register a new profile and return the object
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function register_profile($name, $description=NULL, $perms=NULL, $lang_ctx=NULL) {
		$cvar = 'core_profile_RG_'.$name;
		
		/* local and short cache */
		if(is_object($this->contexts[$name]))
			return($this->contexts[$name]);
			
		/* look at the long cache */
		$cache = $this->_core_cacher->get($cvar);
		if(is_object($cache)) {
			$cache->wf = $this->wf;
			$this->contexts[$name] = $cache;
			return($cache);
		}

		$data = $this->search_profile($name);
		if(!$data) {
			$insert = array(
				'create_time' => time(),
				'name' => $name,
				'description' => base64_encode($description),
				'perms' => $perms
			);
		
			$q = new core_db_insert_id('core_profile', 'id', $insert);
			$this->wf->db->query($q);
			$res = $q->get_result();
			$id = $res['id'];
			
			$this->contexts[$name] = new core_profile_context(
				$this->wf
			);
			
			$this->contexts[$name]->id = (int)$id;
			$this->contexts[$name]->create_time = 
				(int)$insert['create_time'];
			$this->contexts[$name]->name = $insert['name'];
			$this->contexts[$name]->description = base64_decode(
				$insert['description']
			);
			$this->contexts[$name]->perms = $insert['perms'];
		}
		else {
			if($description || $perms) {
				$q = new core_db_update('core_profile');
				$where = array();
				$where['name'] = $name;
				$insert = array();

				if($description)
					$insert['description'] = base64_encode($description);
				if($perms)
					$insert['perms'] = $perms;
				$q->where($where);
				$q->insert($insert);
				$this->wf->db->query($q);
			}
			
			$id = $data[0]['id'];

			/* store into the short cache */
			$this->contexts[$name] = new core_profile_context(
				$this->wf
			);
			
			/* update object information */
			$this->contexts[$name]->id = (int)$id;
			$this->contexts[$name]->create_time = 
				(int)$data[0]['create_time'];
			$this->contexts[$name]->name = $data[0]['name'];
			
			$this->contexts[$name]->description = base64_decode(
				$insert['description'] ?
					$insert['description'] :
					$data[0]['description']
			);
			$this->contexts[$name]->perms = 
				$insert['perms'] ?
					$insert['perms'] :
					$data[0]['perms'];
		}

		$this->store_context($this->contexts[$name], &$name);

		return($this->contexts[$name]);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Opaque function to store the cache context
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function store_context($obj, $name) {
		$cvar = 'core_profile_RG_'.$name;
		
		/* store the objet into the cache */
		unset($obj->wf);
		$this->_core_cacher->store(
			$cvar,
			$obj
		);
		$obj->wf = $this->wf;
		
		return(TRUE);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Function used to search profile
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function search_profile($name=NULL, $id=NULL) {
		$q = new core_db_select('core_profile');
		$where = array();
	
		if($name)
			$where['name'] = $name;
		if($id)
			$where['id'] = $id;
			
		$q->where($where);
		$this->wf->db->query($q);
		$res = $q->get_result();

		return($res);
	}

}
