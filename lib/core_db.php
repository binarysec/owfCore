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
        
/* core_db types flags */
define("WF_AUTOINC",	0x01);
define("WF_PRIMARY",	0x02);
define("WF_UNIQUE",		0x04);
define("WF_UNSIGNED",	0x08);

/* core_db types */
define("WF_VARCHAR",	0x10);
define("WF_SMALLINT",	0x20);
define("WF_INT",		0x30);
define("WF_FLOAT",		0x40);
define("WF_TIME",		0x50);
define("WF_DATA",		0x60);
define("WF_BIGINT",		0x70);

define("WF_PRI",		WF_INT | WF_AUTOINC | WF_PRIMARY);
define("WF_VARCHAR_PRI",WF_VARCHAR | WF_PRIMARY);
define("WF_DATA_PRI",	WF_DATA | WF_PRIMARY);

/* type of joins */
define("WF_JOIN_NATURAL", 	0x001);
define("WF_JOIN_OUTER", 	0x002);
define("WF_JOIN_LEFT",    	0x010);
define("WF_JOIN_RIGHT",    	0x020);
define("WF_JOIN_INNER",    	0x040);
define("WF_JOIN_CROSS",    	0x080);
define("WF_JOIN_STRAIGHT", 	0x100);

/* query elements */
define("WF_QUERY_WHERE",		0x01);
define("WF_QUERY_GROUP",		0x02);
define("WF_QUERY_LIMIT",		0x04);
define("WF_QUERY_ORDER",		0x08);
define("WF_QUERY_ADV_WHERE",	0x10);
define("WF_QUERY_AS",			0x20);

/* type of query */
define("WF_SELECT",						0x100 | WF_QUERY_WHERE | WF_QUERY_ORDER | WF_QUERY_GROUP | WF_QUERY_LIMIT);
define("WF_ADV_SELECT",					0x200 | WF_QUERY_AS | WF_QUERY_ADV_WHERE | WF_QUERY_ORDER | WF_QUERY_GROUP | WF_QUERY_LIMIT);
define("WF_SELECT_DISTINCT",			0x300 | WF_QUERY_ORDER | WF_QUERY_GROUP | WF_QUERY_LIMIT);
define("WF_INSERT",						0x400);
define("WF_INSERT_ID",					0x500);
define("WF_UPDATE",						0x600 | WF_QUERY_WHERE);
define("WF_ADV_UPDATE",					0x700 | WF_QUERY_ADV_WHERE);
define("WF_DELETE",						0x800 | WF_QUERY_WHERE);
define("WF_ADV_DELETE",					0x900 | WF_QUERY_ADV_WHERE);
define("WF_MULTIPLE_INSERT_OR_UPDATE",	0xA00);
define("WF_INDEX",						0xB00);
define("WF_INSERT_MULTIPLE",			0xC00);

/* order define */
define("WF_ASC",              10);
define("WF_DES",              11);
define("WF_DESC",             11);
define("WF_RAND",             12);
define("WF_RANDOM",           12);

/* Request function */
define("WF_REQ_FCT_COUNT",		0x1);
define("WF_REQ_FCT_DISTINCT",	0x2);
define("WF_REQ_FCT_SUM",		0x4);

function core_gettype($value) {
	
	if(is_a($value, "core_db_adv_select"))
		return false;
	
	$type = WF_T_INTEGER;
	
	for($a = 0; $a < strlen($value); $a++) {
		if(	ord($value[$a]) >= 0x30 &&
			ord($value[$a]) <= 0x39)
			;
		elseif(
			$value[$a] == '.' &&
			$type == WF_T_INTEGER
			) {
				$type = WF_T_DOUBLE;
		}
		else
			return WF_T_STRING;
	}
	
	return $type;
}

abstract class core_db {
	public $wf = NULL;
	abstract public function load($dbconf);
	abstract public function get_driver_banner();
	abstract public function get_driver_name();
	abstract public function register_zone($name, $description, $struct);
	abstract public function unregister_zone($name);
	abstract public function query($query_obj);
	abstract public function get_configuration();
	abstract public function test_configuration($dbconf);
	abstract public function get_request_counter();
	abstract public function get_last_insert_id($seq=null);
}

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 *
 *		Queries scheme
 * 
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

abstract class core_db_query {
	var $type = 0;
	
	public function __construct($type) { $this->type = $type; }
}

abstract class core_db_query_simple extends core_db_query {
	public function __construct($type, $zone, $arr = null, $where = null) {
		parent::__construct($type);
		$this->zone = $zone;
		$this->insert($arr);
		$this->where($where);
	}
	public function where($where) {
		$this->where = $where;
	}
	public function insert($insert) {
		$this->arr = $insert;
	}
}

abstract class core_db_query_adv extends core_db_query {
	public function __construct($type, $zone) {
		parent::__construct($type);
		$this->zone = $zone;
	}
	
	var $cond_matrix = array();

	public function do_open() {
		array_push($this->cond_matrix, array(1));
	}
	public function do_close() {
		array_push($this->cond_matrix, array(2));
	}
	public function do_or() {
		array_push($this->cond_matrix, array(3));
	}
	public function do_and() {
		array_push($this->cond_matrix, array(4));
	}
	/* keo on 11/12/2008 : param $val no longer required
	   cause condition IS [NOT] NULL doens't require value */
	public function do_comp($var, $sign, $val = null) {
		$exist = false;
		
		/* if query contains aliases, check if comparison use existing alias */
		if($this->type & WF_QUERY_AS) {
			$var_exist = false;
			$val_exist = false;
			
			/* chech $var if alias is given */
			$var_alias = current(explode(".", $var));
			foreach($this->as as $als)
				if(isset($als["A"]) && $als["A"] == $var_alias)
					$var_exist = true;
			
			/* chech $val if alias is given */
			if($val != null && !is_object($val)) {
				$val_alias = current(explode(".", $val));
				foreach($this->as as $als)
					if(isset($als["A"]) && $als["A"] == $val_alias)
						$val_exist = true;
			}
			
			if($sign == "==") {
				if(!$var_exist)
					throw new wf_exception(null, WF_EXC_PRIVATE,
						"Calling do_comp($var, \"$sign\" [,..]) but the alias $var_alias was not registered"
					);
				if(!$val_exist)
					throw new wf_exception(null, WF_EXC_PRIVATE,
						"Calling do_comp($var, \"$sign\", $val) but the alias $val_alias was not registered"
					);
			}
			
			$exist = $var_exist && $val_exist;
		}
		
		array_push($this->cond_matrix, array(5, $var, $sign, $val, $exist));
	}
}

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 *
 *		Queries declaration
 * 
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

class core_db_insert extends core_db_query_simple {
	public function __construct($zone, $arr) {
		parent::__construct(WF_INSERT, $zone, $arr);
	}
}

class core_db_insert_multiple extends core_db_query_simple {
	public function __construct($zone, array $arr) {
		parent::__construct(WF_INSERT_MULTIPLE, $zone, $arr);
	}
	
	public function insert($insert) {
		$firsttime = true;
		$struct = array();
		foreach($insert as $v) {
			if(!is_array($v)) {
				throw new wf_exception(null, WF_EXC_PRIVATE,
					"core_db_insert_multiple requires array of array for data"
				);
			}
			
			foreach($v as $k => $field)
				if($firsttime)
					$struct[] = $k;
				elseif(!in_array($k, $struct))
					throw new wf_exception(null, WF_EXC_PRIVATE,
						"core_db_insert_multiple: cannot have different keys for multiple fieldsets"
					);
			
			$firsttime = false;
		}
		
		parent::insert($insert);
	}
}

class core_db_insert_id extends core_db_query_simple {
	var $result = null;
	public function __construct($zone, $id, $arr) {
		parent::__construct(WF_INSERT_ID, $zone, $arr);
		$this->id = $id;
	}
	public function get_result() {
		return $this->result;
	}
}

class core_db_update extends core_db_query_simple {
	public function __construct($zone, $arr = null, $where = null) {
		parent::__construct(WF_UPDATE, $zone, $arr, $where);
	}
}

class core_db_delete extends core_db_query_simple {
	public function __construct($zone, $where) {
		parent::__construct(WF_DELETE, $zone, null, $where);
	}
}

class core_db_select extends core_db_query_simple {
	var $fields = null;
	var $order = null;
	var $group = null;
	var $result = null;
	var $limit = -1;
	var $offset = -1;
	
	public function __construct($zone, $fields = null, $where = null) {
		parent::__construct(WF_SELECT, $zone, null, $where);
		$this->fields($fields);
	}
	
	public function fields($fields) {
		$this->fields = $fields;
	}
	
	public function order($order) {
		$this->order = $order;
	}

	public function group($group) {
		$this->group = $group;
	}
	
	public function limit($limit, $offset=-1) {
		$this->limit = intval($limit);
		if($offset > -1)
			$this->offset = intval($offset);
	}
	
	public function get_result() {
		return($this->result);
	}
}

class core_db_multiple_insert_or_update extends core_db_query {
	var $zone = NULL;
	var $arr = NULL;
	var $up = NULL;
	public function __construct($zone, $arr,$up=NULL) {
		$this->type = WF_MULTIPLE_INSERT_OR_UPDATE;
		$this->zone = $zone;
		$this->arr = $arr;
		if($up)
			$this->up = $up;
	}
	public function up($up) {
		$this->up = $up;
	}
}

class core_db_adv_update extends core_db_query_adv {
	var $arr = null;

	public function __construct($zone) {
		parent::__construct(WF_ADV_UPDATE, $zone);
	}
	public function insert($insert) {
		$this->arr = $insert;
	}
}

class core_db_adv_delete extends core_db_query_adv {
	public function __construct($zone) {
		parent::__construct(WF_ADV_DELETE, $zone);
	}
}

class core_db_adv_select extends core_db_query_adv {
	var $fields = array();
	var $as = array();
	var $where = array();
	var $order = array();
	var $group = array();
	var $result = NULL;
	
	var $limit = -1;
	var $offset = -1;
	
	var $req_fct = NULL;
	
	public function __construct($zone = null) {
		parent::__construct(WF_ADV_SELECT, $zone);
	}
	
	public function fields($fields) {
		array_push($this->fields, $fields);
	}
	
	public function alias($alias, $tab, $join = null) {
		$insert = array(
			"A" => $alias,
			"T" => $tab
		);
		if(!is_null($join) && is_a($join, "core_db_join"))
			$insert["J"] = $join;
		array_push($this->as, $insert);
	}
	
	public function order($order) {
		$this->order = $order;
	}

	public function group($group) {
		$this->group = $group;
	}
	
	public function limit($limit, $offset=-1) {
		$this->limit = intval($limit);
		if($offset > -1)
			$this->offset = intval($offset);
	}
	
	public function get_result() {
		return($this->result);
	}
	
	public function request_function($type) {
		$this->req_fct = $type;
	}
}

class core_db_select_distinct extends core_db_query {
	var $zone = NULL;
	var $fields = NULL;
	var $order = NULL;
	var $group = NULL;
	var $result = NULL;
	
	var $limit = -1;
	var $offset = -1;
	
	public function __construct($zone, $fields=NULL) {
		$this->type = WF_SELECT_DISTINCT;
		$this->zone = $zone;
		$this->fields = $fields;
	}
	
	public function fields($fields) {
		$this->fields = $fields;
	}
	
	public function order($order) {
		$this->order = $order;
	}

	public function group($group) {
		$this->group = $group;
	}
	
	public function limit($limit, $offset=-1) {
		$this->limit = intval($limit);
		if($offset > -1)
			$this->offset = intval($offset);
	}
	
	public function get_result() {
		return($this->result);
	}
}

class core_db_index extends core_db_query {
	public $zone;
	public $indexes = array();
	
	public function __construct($table) {
		$this->type = WF_INDEX;
		$this->zone = $table;
	}
	
	public function register($name, $cols, $unique = false) {
		if(!array_key_exists($name, $this->indexes))
			$this->indexes[$name] = array();
		$idx = &$this->indexes[$name];
		if(is_array($cols)) {
			foreach($cols as $col) 
				$this->checkup($idx, $col, $unique);
		}
		else
			$this->checkup($idx, $cols, $unique);
	}
	
	private function checkup(&$idx, $colname, $unique) {
		$idx[$colname] = $unique;
	}
	
}

class core_db_join {
	public function __construct($join) {
		$this->join = $join;
	}
	
	public function build() {
		$ret = "";
		if(($this->join & WF_JOIN_INNER) == WF_JOIN_INNER)
			$ret = "INNER JOIN";
		elseif(($this->join & WF_JOIN_CROSS) == WF_JOIN_CROSS)
			$ret = "CROSS JOIN";
		elseif(($this->join & WF_JOIN_STRAIGHT) == WF_JOIN_STRAIGHT)
			$ret = "STRAIGHT_JOIN";
		elseif(
			($this->join & WF_JOIN_LEFT) == WF_JOIN_LEFT ||
			($this->join & WF_JOIN_RIGHT) == WF_JOIN_RIGHT
			) {
				if(($this->join & WF_JOIN_NATURAL) == WF_JOIN_NATURAL)
					$ret .= "NATURAL ";
				if(($this->join & WF_JOIN_LEFT) == WF_JOIN_LEFT)
					$ret .= "LEFT ";
				elseif(($this->join & WF_JOIN_RIGHT) == WF_JOIN_RIGHT)
					$ret .= "RIGHT ";
				if(($this->join & WF_JOIN_OUTER) == WF_JOIN_OUTER)
					$ret .= "OUTER ";
				$ret .= "JOIN";
		}
		return $ret;
	}
	
	var $cond_matrix = array();

	public function do_open() {
		array_push($this->cond_matrix, array(1));
	}
	public function do_close() {
		array_push($this->cond_matrix, array(2));
	}
	public function do_or() {
		array_push($this->cond_matrix, array(3));
	}
	public function do_and() {
		array_push($this->cond_matrix, array(4));
	}
	public function do_comp($var, $sign, $val) {
		array_push($this->cond_matrix, array(5, $var, $sign, $val));
	}
}


class core_db_device {
	var $driver_arr = array();
	
	public function __construct() {
		$dir = dirname(__FILE__);
		$sd = scandir($dir);
		foreach($sd as $k => $v) {
			if(strncmp($v, "core_db_", 7) == 0) {
				$patterns = array('/core_db_/', '/.php/');
				$replacements = array('', '');
				$driver = preg_replace($patterns, $replacements, $v);
				$this->load_driver($driver);
			}
		}
	}
	
	private function load_driver($name) {
		$driver = "core_db_".$name;
		$o = new ${driver};
		$this->driver_arr[$name] = $o;
	}
}
