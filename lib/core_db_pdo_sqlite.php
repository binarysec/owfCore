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

class core_db_pdo_sqlite extends core_db {
	var $hdl = NULL;
	var $request_c = 0;
	
	var $schema = array();
	
	public function load($dbconf) {
		
		$file = isset($dbconf["file"]) ? $dbconf["file"] : "";
		
		/* open sqlite database */
		if($file[0] != '/')
			$file = dirname(__FILE__)."/$file";
	
		try {
			$this->hdl = new PDO("sqlite:$file");
		} 
		catch (PDOException $e) {
			throw new wf_exception(
				$this,
				WF_EXC_PRIVATE,
				"PDO::SQLite: Could not load database: ".$e->getMessage()
			);
		}
		
		/* get cacher */
		$this->a_core_cacher = $this->wf->core_cacher();
		
		/* check zone table */
		$this->check_zone_tab();

		return(TRUE);
	}

	public function get_last_insert_id($seq=null) {
		return($this->hdl->lastInsertId($seq));
	}

	public function get_driver_name() {
		return("SQLite");
	}
	
	public function get_driver_banner() {
		return("SQLite/".$this->hdl->getAttribute(PDO::ATTR_SERVER_VERSION));
	}
	
	public function get_request_counter() {
		return($this->request_c);
	}
	
	
	/*
	register a new data zone
	*/
	public function register_zone($name, $description, $struct) {
		$res = $this->get_zone($name);
		if(!isset($res[0])) {
			$this->create_table($name, $struct);
			$this->create_zone($name, $struct, $description);
		}
		else {
			if(!$this->table_exists($name)) 
				$this->create_table($name, $struct);
				
			$this->check_for_data_translation($name, $description, $struct, $res);
		}
		
		$this->schema[$name] = $struct;

	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Unregister a DB zone
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function unregister_zone($name) {
		$this->drop_zone($name);
		$this->drop_table($name);
		return(TRUE);
	}
	
	private function check_for_data_translation($name, $description, $struct, $info) {
		$change = FALSE;
		$selector = new core_db_select($name);
		$fields_selector = array();
		$old_struct = array();
		foreach($info as $k => $v) {
			$col_name = &$v["b.name"];
			$col_type = &$v["b.type"];
			if($struct[$col_name]) {
				if($col_type != $struct[$col_name])
					$change = TRUE;
				$old_struct[$col_name] = $struct[$col_name];
				array_push($fields_selector, $col_name);
			}
			else
				$change = TRUE;
		}
		foreach($struct as $k => $v) {
			if(!$old_struct[$k])
				$change = TRUE;
		}
		if($change) {
			$selector->fields($fields_selector);
			$this->sql_query("BEGIN TRANSACTION;");
			$this->query($selector);
			$res = $selector->get_result();
			$this->info("* Design has changed for the zone '$name' please wait... ");
			$tmp_name = "new_$name";
			$this->create_table($tmp_name, $struct);
			$el = 0;
			foreach($res as $k => $v) {
				$q = new core_db_insert($tmp_name, $v);
				$this->query($q);
				$el++;
			}
			$this->drop_table($name);
			$this->rename_table($tmp_name, $name);
			$this->drop_zone($name);
			$this->create_zone($name, $struct, $description);
			$this->sql_query("COMMIT;");
			$this->info("$el elements updated.\n");
		}
	}
	
	private function info($data) {
// 		if(count($_REQUEST) == 0) 
// 			echo $data;
	}
	
	/* backyard functions :] */
	private function sql_query($query, $values=NULL) {
		$q = $this->hdl->prepare($query);
		if(!$q) {
			$er = $this->hdl->errorInfo();
			
			throw new wf_exception(
				$this,
				WF_EXC_PRIVATE,
				array(
					"PDO::SQLite->prepare()#$er[0]: $er[2]",
					"PDO::SQLite->prepare()# SQL request:",
					"<pre>$query</pre>"
				)
			);
		}
		
		$q->execute($values);
		$er = $q->errorInfo();
		if($er[0] != 0) {
			$er = $this->hdl->errorInfo();
			throw new wf_exception(
				$this,
				WF_EXC_PRIVATE,
				array(
					"PDO::SQLite->execute()#$er[0]: $er[2]",
					"PDO::SQLite->execute()# SQL request:",
					"<pre>$query</pre>"
				)
			);
		}
		
		/* log */
		//echo('SQLite ORDER #'.($this->request_c + 1).': '.$query.'<br />');

		$this->request_c++;
		
		return($q);
	}
	

	private function fetch_result($query_object) {
		$res = $query_object->fetchAll(PDO::FETCH_NAMED);
		return($res);
	}
	
	public function query($query_obj) {
		
		/* general vars */
		$prepare_value = array();
		$zone = "";
		$fields = "";
		$key = "";
		$val = "";
		$where = "";
		$order = "";
		$group = "";
		$limit = "";
		$offset = "";
		
		/* fields */
		if($query_obj->type == WF_INSERT || $query_obj->type == WF_INSERT_ID) {
			foreach($query_obj->arr as $k => $v) {
				$key .= empty($key) ? "`$k`" : ", `$k`";
				$val .= empty($val) ? "?" : ", ?";
				array_push($prepare_value, $v);
			}
		}
		elseif($query_obj->type == WF_UPDATE || $query_obj->type == WF_ADV_UPDATE) {
			foreach($query_obj->arr as $k => $v) {
				$fields .= empty($fields) ? "`$k` = ?" : ", `$k` = ?";
				array_push($prepare_value, $v);
			}
		}
		elseif(
			$query_obj->type == WF_SELECT ||
			$query_obj->type == WF_SELECT_DISTINCT ||
			$query_obj->type == WF_ADV_SELECT
			) {
				if($query_obj->fields != NULL) {
					for($a = 0; $a < count($query_obj->fields); $a++) {
						if(!empty($fields))
							$fields .= ", ";
						$fields .= $query_obj->fields[$a];
						if(	$query_obj->type == WF_ADV_SELECT &&
							!strchr($query_obj->fields[$a], "*")
							)
								$fields .= ' AS "'.$query_obj->fields[$a].'"';
					}
				}
				/* Si il y a plus d'un alias alors il faut aligner les structures */
				elseif($query_obj->type == WF_ADV_SELECT && count($query_obj->as) > 1) {
					for($a = 0; $a < count($query_obj->as); $a++) {
						$alias = $query_obj->as[$a]["A"];
						$tab = $query_obj->as[$a]["T"];
						$sch = $this->schema[$tab];
						foreach($sch as $k => $v) {
							if(!empty($fields))
								$fields .= ",";
							$var = "$alias.$k";
							$fields .= $var.' AS "'.$var.'"';
						}
					}
				}
				else
					$fields = "*";
		}
		
		/* zones */
		if($query_obj->type & WF_QUERY_AS && count($query_obj->as) > 0) {
			for($a = 0; $a < count($query_obj->as); $a++) {
				$cond = "";
				if(!empty($zone))
					$zone .= ",";
				$zone .= $query_obj->as[$a]["T"]." AS ";
				$zone .= $query_obj->as[$a]["A"];
			}
		}
		else
			$zone = $query_obj->zone;
		
		/* where */
		if($query_obj->type & WF_QUERY_WHERE && $query_obj->where != NULL) {
			foreach($query_obj->where as $k => $v) {
				$where .= empty($where) ?
					"WHERE `$k` = ?" :
					" AND `$k` = ?";
				
				array_push($prepare_value, $v);
			}
		}
		
		/* advanced where */
		elseif($query_obj->type & WF_QUERY_ADV_WHERE) {
			$atom = 0;
			if(count($query_obj->cond_matrix) > 0)
				$where .= " WHERE";
				
			foreach($query_obj->cond_matrix as $k) {
				switch($k[0]) {
					case 1:
						switch($atom) {
							case 1: $where .= ' OR '; break;
							case 2: case 3: $where .= ' AND '; break;
						}
						$atom = 0;
						$where .= '(';
						break;
					case 2: $where .= ')'; break;
					case 3: $atom = 1; break;
					case 4: $atom = 2; break;
					case 5:
						switch($atom) {
							case 0: $where .= ' '; $atom = 3; break;
							case 1: $where .= ' OR '; $atom = 3; break;
							case 2: $where .= ' AND '; $atom = 3; break;
							case 3: $where .= ' AND '; break;
						}
						$where .= $this->get_query_var(
							$k[1], 
							$k[2], 
							$k[3], 
							$prepare_value,
							isset($k[4]) ? $k[4] : true
						);
						break;
				}
			}
		}
		
		/* order */
		if($query_obj->type & WF_QUERY_ORDER && $query_obj->order != NULL) {
			$first = TRUE;
			foreach($query_obj->order as $k => $v) {
				$order .= $first ? "ORDER BY " : ", ";
				if($v == WF_RAND)
					$order .= "RANDOM()";
				else {
					$order .= "`$k`";
					if($v == WF_ASC)
						$order .= " ASC";
					else//if($v == WF_DESC)
						$order .= " DESC";
				}
				$first = FALSE;
			}
		}
		
		/* group by */
		if($query_obj->type & WF_QUERY_GROUP && $query_obj->group != NULL) {
			foreach($query_obj->group as $k)
				$group = empty($group) ?
					"GROUP BY `$k`" :
					", `$k`";
		}
		
		/* limit and offset */
		if($query_obj->type & WF_QUERY_LIMIT && $query_obj->limit > -1) {
			$limit = "LIMIT ".intval($query_obj->limit);
			if($query_obj->offset > -1)
				$offset = "OFFSET ".intval($query_obj->offset);
		}
		
		
		/* Query */
		if($query_obj->type == WF_SELECT) {
			$res = $this->sql_query(
				"SELECT $fields FROM $zone $where $group $order $limit $offset",
				$prepare_value
			);
			$query_obj->result = $this->fetch_result($res);
		}
		elseif($query_obj->type == WF_SELECT_DISTINCT) {
			$res = $this->sql_query(
				"SELECT DISTINCT $fields FROM $zone $group $order $limit $offset"
			);
			$query_obj->result = $this->fetch_result($res);
		}
		elseif($query_obj->type == WF_UPDATE || $query_obj->type == WF_ADV_UPDATE) {
			$this->sql_query(
				"UPDATE $zone SET $fields $where",
				$prepare_value
			);
		}
		elseif($query_obj->type == WF_DELETE || $query_obj->type == WF_ADV_DELETE) {
			$this->sql_query(
				"DELETE FROM $zone $where",
				$prepare_value
			);
		}
		elseif($query_obj->type == WF_INSERT) {
			$this->sql_query(
				"INSERT INTO ".$query_obj->zone." ($key) VALUES ($val);",
				$prepare_value
			);
		}
		elseif($query_obj->type == WF_INSERT_ID) {
			$this->sql_query(
				"INSERT INTO $zone ($key) VALUES ($val);",
				$prepare_value
			);
			$id = $this->hdl->lastInsertId();
			
			/* chope touted les infos */
			$q = new core_db_select($query_obj->zone);
			$where = array();
			$where[$query_obj->id] = $id;
			$q->where($where);
			$this->query($q);

			$query_obj->result = $q->get_result();
			$query_obj->result = $query_obj->result[0];
		}
		elseif($query_obj->type == WF_ADV_SELECT) {
			if($query_obj->req_fct & WF_REQ_FCT_COUNT)
				$fields = "COUNT(*)";
			elseif($query_obj->req_fct & WF_REQ_FCT_SUM)
				$fields = "SUM(*)";
			
			$select = $query_obj->req_fct & WF_REQ_FCT_DISTINCT ?
				"SELECT DISTINCT" : "SELECT";
			
			$res = $this->sql_query(
				"$select $fields FROM $zone $where $group $order $limit $offset",
				$prepare_value
			);
			$query_obj->result = $this->fetch_result($res);
		}
		elseif($query_obj->type == WF_INDEX) {
			// ...
		}
	}
	
	public function get_configuration() {
		$ret = array(
			"file" => array(
				"default" => "/var/lib/wafd/data/db.sqlite",
				"size" => "25",
				"lang" => array(
					"fr" => "Fichier base de donn&eacute;e",
					"eng" => "Database filename"
				)
			),
			"perm" => array(
				"default" => "700",
				"size" => "6",
				"lang" => array(
					"fr" => "Permission du fichier",
					"eng" => "File permission"
				)
			)
		);
		
		return($ret);
	}
	
	public function test_configuration($dbconf) {
		return(TRUE);
	}
	
	private function get_query_var($var, $sign, $sval, &$prepare_values, $table_alias_exist = true) {
		$cond = NULL;
		$val = $this->safe_input($sval);
		switch($sign) {
			case '=':
				$cond = "$var = ?";
				array_push($prepare_values, $sval);
				break;
			case '==':
				if(!$table_alias_exist) {
					$cond = "$var = ?";
					array_push($prepare_values, $sval);
				}
				else
					$cond = "$var = $val";
				break;
			case '~=':
				$cond = "$var LIKE ?";
				array_push($prepare_values, $sval);
				break;
			case '>':
				$cond = "$var > ?";
				array_push($prepare_values, $sval);
				break;
			case '<':
				$cond = "$var < ?";
				array_push($prepare_values, $sval);
				break;
			case '>=':
				$cond = "$var >= ?";
				array_push($prepare_values, $sval);
				break;
			case '<=':
				$cond = "$var <= ?";
				array_push($prepare_values, $sval);
				break;
			case '!=' :
				$cond = "$var <> ?";
				array_push($prepare_values, $sval);
				break;
			/* added by keo on 11/12/2008 : IS NULL and IS NOT NULL conditions */
			case '!':
				$cond = "$var IS NULL";
				break;
			case '!!':
				$cond = "$var IS NOT NULL";
				break;
		}
		
		return($cond);
	}
	
	/*
	get the wrapped type 
	*/
	private function get_struct_type($item) {
		if($item & WF_PRIMARY)
			return("INTEGER NOT NULL PRIMARY KEY");
		
		switch($item & 0xF0) {
			case WF_VARCHAR :
				return("VARCHAR(255)");
			case WF_SMALLINT :
				return("SMALLINT");
			case WF_INT :
			case WF_TIME :
				return("INT");
			case WF_BIGINT :
				return("BIGINT");
			case WF_FLOAT :
				return("FLOAT");
			case WF_DATA :
				return("BLOB");
		}
	}
	
	private function create_table($name, $struct) {
		$query = "CREATE TABLE \"$name\" (";
		$vir = FALSE;
		foreach($struct as $k => $v) {
			if($vir)
				$query .= ",";
			
			$query .= "`$k` ".$this->get_struct_type($v);
			$vir = TRUE;
		}
		$query .= ")";
		$this->sql_query($query);
	}
	
	private function rename_table($old, $new) {
		$query = 'ALTER TABLE "'.$old.'" RENAME TO "'.$new.'"';
		$this->sql_query($query);
		
		/* remove the cache */
		$cvar = "core_db_pdo_sqlite_table_$old";
		$this->a_core_cacher->delete($cvar);
		$cvar = "core_db_pdo_sqlite_zone_$old";
		$this->a_core_cacher->delete($cvar);
	}
	
	private function drop_table($name) {
		$query = "DROP TABLE $name";
		$this->sql_query($query);
		
		/* remove the cache */
		$cvar = "core_db_pdo_sqlite_table_$name";
		$this->a_core_cacher->delete($cvar);
		$cvar = "core_db_pdo_sqlite_zone_$name";
		$this->a_core_cacher->delete($cvar);
	}
	
	private function table_exists($name) {
		$cvar = "core_db_pdo_sqlite_table_$name";
		
		/* check if possible to get data from the cache */
		if(($res = $this->a_core_cacher->get($cvar)) != NULL)
			return($res);
			
		$q = new core_db_select("sqlite_master");

		$where = array();
		$where["name"] = $name;
		$q->where($where);
		$this->query($q);
		$res = $q->get_result();
		
		/* push table information */
		$this->a_core_cacher->store($cvar, isset($res[0]));
		
		return isset($res[0]);
	}
	
	private function drop_zone($name) {
		$res = $this->get_zone($name);
		$zone = &$res[0];
		if($zone) {
			$q = new core_db_delete(
				"zone",
				array("id" => $zone["a.id"])
			);
			$this->query($q);
				
			$q = new core_db_delete(
				"zone_col",
				array("zone_id" => $zone["a.id"])
			);
			$this->query($q);
		}
		
		/* remove the cache */
		$cvar = "core_db_pdo_sqlite_table_$name";
		$this->a_core_cacher->delete($cvar);
		$cvar = "core_db_pdo_sqlite_zone_$name";
		$this->a_core_cacher->delete($cvar);
	}
	
	private function create_zone($name, $struct, $description) {
		$insert = array(
			"name" => $name,
			"description" => $description
		);
		$q = new core_db_insert_id("zone", "id", $insert);
		$this->query($q);
			
		$insert_id = $q->get_result();
		
		foreach($struct as $k => $v) {
			$insert = array(
				"zone_id" => $insert_id["id"],
				"name" => $k,
				"type" => $v
			);
			$q = new core_db_insert("zone_col", $insert);
			$this->query($q);
		}
		
		/* remove the cache */
		$cvar = "core_db_pdo_sqlite_table_$name";
		$this->a_core_cacher->delete($cvar);
		$cvar = "core_db_pdo_sqlite_zone_$name";
		$this->a_core_cacher->delete($cvar);
	}
	
	public function get_zone($name) {
		/* try to retrieve zone from the cache */
		$cvar = "core_db_pdo_sqlite_zone_$name";
		if(($res = $this->a_core_cacher->get($cvar)) != NULL)
			return($res);

		$q = new core_db_adv_select;
		
		$q->alias("a", "zone");
		$q->alias("b", "zone_col");
		
		$q->do_comp("a.name", "=", $name);
		$q->do_comp("b.zone_id", "==", "a.id");
		
		$this->query($q);
		$res = $q->get_result();
		
		/* push the zone into the cache */
		$this->a_core_cacher->store(
			$cvar,
			$res
		);
		
		return($res);
	}
	
	private function check_zone_tab() {
		$zone = array(
			"id" => WF_PRI,
			"name" => WF_VARCHAR,
			"description" => WF_VARCHAR
		);
		$this->schema["zone"] = $zone;
		
		$zone_col = array(
			"zone_id" => WF_INT,
			"name" => WF_VARCHAR,
			"type" => WF_INT
		);
		$this->schema["zone_col"] = $zone_col;
		
		if(!$this->table_exists("zone")) {
			$this->create_table("zone", $zone);
			$this->create_table("zone_col", $zone_col);
		}
	}
	
	
	private function safe_input($i) {
		return(stripslashes($i));
	}
	
}
