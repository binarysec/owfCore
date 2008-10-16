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

/* type supported by core_db */
define("WF_VARCHAR",   1);
define("WF_SMALLINT",  2);
define("WF_INT",       3);
define("WF_FLOAT",     4);
define("WF_TIME",      5);
define("WF_DATA",      6);
define("WF_PRI",       7);

/* type of query */
define("WF_SELECT",            1);
define("WF_ADV_SELECT",        2);
define("WF_INSERT",            3);
define("WF_INSERT_ID",         4);
define("WF_UPDATE",            5);
define("WF_DELETE",            6);
define("WF_ADV_DELETE",        7);
define("WF_SELECT_DISTINCT",   8);

/* order define */
define("WF_ASC",              10);
define("WF_DES",              11);

/* Request function */
define("WF_REQ_FCT_COUNT",    20);

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

class core_db_query {
	var $type = 0;
}

class core_db_insert extends core_db_query {
	var $zone = NULL;
	var $arr = NULL;
	public function __construct($zone, $arr) {
		$this->type = WF_INSERT;
		$this->zone = $zone;
		$this->arr = $arr;
	}
}


class core_db_insert_id extends core_db_query {
	var $zone = NULL;
	var $arr = NULL;
	var $id = NULL;
	var $result = NULL;
	public function __construct($zone, $id, $arr) {
		$this->type = WF_INSERT_ID;
		$this->zone = $zone;
		$this->id = $id;
		$this->arr = $arr;
	}
	public function get_result() {
		return($this->result);
	}
}

class core_db_update extends core_db_query {
	var $zone = NULL;
	var $arr = NULL;
	var $where = NULL;
	public function __construct($zone, $arr=NULL, $where=NULL) {
		$this->type = WF_UPDATE;
		$this->zone = $zone;
		$this->arr = $arr;
		$this->where = $where;
	}
	public function where($where) {
		$this->where = $where;
	}
	public function insert($insert) {
		$this->arr = $insert;
	}
	
}

class core_db_adv_select extends core_db_query {
	var $zone = NULL;
	var $fields = array();
	var $as = array();
	var $where = array();
	var $order = array();
	var $group = array();
	var $result = NULL;
	
	var $limit = -1;
	var $offset = -1;
	
	var $req_fct = NULL;
	
	public function __construct($zone=NULL) {
		$this->type = WF_ADV_SELECT;
		$this->zone = $zone;
	}
	
	public function fields($fields) {
		array_push($this->fields, $fields);
	}
	
	public function alias($alias, $tab) {
		$insert = array(
			"A" => $alias,
			"T" => $tab
		);
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


class core_db_select extends core_db_query {
	var $zone = NULL;
	var $fields = NULL;
	var $where = NULL;
	var $order = NULL;
	var $group = NULL;
	var $result = NULL;
	
	var $limit = -1;
	var $offset = -1;
	
	public function __construct($zone, $fields=NULL, $where=NULL) {
		$this->type = WF_SELECT;
		$this->zone = $zone;
		$this->fields = $fields;
		$this->where = $where;
	}
	
	public function where($where) {
		$this->where = $where;
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

class core_db_delete extends core_db_query {
	var $zone = NULL;
	var $where = NULL;
	public function __construct($zone, $where) {
		$this->type = WF_DELETE;
		$this->zone = $zone;
		$this->where = $where;
	}
}

class core_db_adv_delete extends core_db_query {
	var $zone = NULL;

	public function __construct($zone) {
		$this->type = WF_ADV_DELETE;
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
	public function do_comp($var, $sign, $val) {
		array_push($this->cond_matrix, array(5, $var, $sign, $val));
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


?>