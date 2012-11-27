<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * Web Framework 1                                       *
 * BinarySEC (c) (2000-2008) / www.binarysec.com         *
 * Author: Thomas Dijoux <td@binarysec.com>              *
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

class core_db_pdo_pgsql extends core_db {
	private $a_core_cacher;
	var $hdl = NULL;
	var $request_c = 0;
	
	var $schema = array();
	
	public function load($dbconf) {
		/* open pgsql database */
		try {
			$dsn  = "pgsql:";

			$dsn .= "dbname=".$dbconf["dbname"];
			if($dbconf["host"])
				$dsn .= ";host=".$dbconf["host"];
			if($dbconf["port"])
				$dsn .= ";port=".$dbconf["port"];
				
			$user = $dbconf["user"];
			$pass = $dbconf["pass"];
			
			$this->hdl = new PDO($dsn, $user, $pass);
		} 
		catch (PDOException $e) {
			print "PgSQL: " . $e->getMessage() . " ($dsn)<br/>";
			die();
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
	
	public function is_usable() {
		$d = PDO::getAvailableDrivers();
		foreach($d as $v) {
			if($v == "pgsql")
				return(TRUE);
		}
		return(FALSE);
	}
	
	public function get_driver_name() {
		return("PostGreSQL");
	}
	
	public function get_driver_banner() {
		return("PostGreSQL/".$this->hdl->getAttribute(PDO::ATTR_SERVER_VERSION));
	}
	
	public function get_request_counter() {
		return($this->request_c);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Register a new DB zone
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
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
		foreach($info as $k => $v)
			$all[$v["b.name"]] = (int)$v["b.type"];

		$change = FALSE;
		
		/* vérifi les élément à ajouter */
		foreach($struct as $k => $v) {
			if(!$all[$k]) {
				$type = $this->get_struct_type($v);
				$query = "ALTER TABLE $name ADD $k $type ;";
				$this->sql_query($query);
				$change = TRUE;
			}
		}
		
		/* vérifi les élément à enlever */
		foreach($all as $k => $v) {
			if(!$struct[$k]) {
				$query = "ALTER TABLE $name DROP $k ;";
				$this->sql_query($query);
				$change = TRUE;
			}

		}

		if($change == TRUE) {
			$this->drop_zone($name);
			$this->create_zone($name, $struct, $description);
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
			echo "* PgSQL->prepare()#$er[0]: $er[2]\n";
			echo "*            \_ SQL request: $query\n";
			die();
		}

		$q->execute($values);
		$er = $q->errorInfo();
		if($er[0] != 0) {
			$er = $this->hdl->errorInfo();
			echo "* PgSQL->execute()#$er[0]: $er[2]\n";
			echo "*            \_ SQL request: $query\n";
			die();
		}

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
				$key .= empty($key) ? "\"$k\"" : ", \"$k\"";
				$val .= empty($val) ? "?" : ", ?";
				array_push($prepare_value, $v);
			}
		}
		elseif($query_obj->type == WF_UPDATE || $query_obj->type == WF_ADV_UPDATE) {
			foreach($query_obj->arr as $k => $v) {
				$fields .= empty($fields) ? "\"$k\" = ?" : ", \"$k\" = ?";
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
				if(!empty($zone))
					$zone .= ",";
				$zone .= '"'.$query_obj->as[$a]["T"]."\" AS ";
				$zone .= $query_obj->as[$a]["A"];
			}
		}
		else
			$zone = $query_obj->zone;
		
		/* where */
		if($query_obj->type & WF_QUERY_WHERE && $query_obj->where != NULL) {
			foreach($query_obj->where as $k => $v) {
				$where .= empty($where) ?
					"WHERE \"$k\" = ?" :
					" AND \"$k\" = ?";
				
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
					$order .= "RAND()";
				else {
					$order .= "\"$k\"";
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
				"SELECT $fields FROM \"$zone\" $where $group $order $limit $offset",
				$prepare_value
			);
			$query_obj->result = $this->fetch_result($res);
#			$i = 0;
#			foreach($query_obj->result as $result) {
#				foreach($result as $k => $v) {
#					if(gettype($v) == "resource") {
#						$query_obj->result[$i][$k] = stream_get_contents($v);
#					}
#				}
#				$i++;
#			}
		}
		elseif($query_obj->type == WF_SELECT_DISTINCT) {
			$res = $this->sql_query(
				"SELECT DISTINCT $fields FROM \"$zone\" $group $order $limit $offset"
			);
			$query_obj->result = $this->fetch_result($res);
		}
		elseif($query_obj->type == WF_UPDATE || $query_obj->type == WF_ADV_UPDATE) {
			$this->sql_query(
				"UPDATE \"$zone\" SET $fields $where",
				$prepare_value
			);
		}
		elseif($query_obj->type == WF_DELETE || $query_obj->type == WF_ADV_DELETE) {
			$this->sql_query(
				"DELETE FROM \"$zone\" $where",
				$prepare_value
			);
		}
		elseif($query_obj->type == WF_INSERT) {
			$this->sql_query(
				"INSERT INTO \"$zone\" ($key) VALUES ($val);",
				$prepare_value
			);
		}
		/* Insert query with ID */
		elseif($query_obj->type == WF_INSERT_ID) {
			$this->sql_query(
				"INSERT INTO \"$zone\" ($key) VALUES ($val);",
				$prepare_value
			);
			
			$id = $this->hdl->lastInsertId($query_obj->zone."_id_seq");
			
			/* chope toute les infos */
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
				
			$query = "$select $fields FROM $zone $where $group $order $limit $offset";
			
			$res = $this->sql_query($query, $prepare_value);
			$query_obj->result = $this->fetch_result($res);
			
			if($query_obj->req_fct & WF_REQ_FCT_COUNT)
				$query_obj->result[0]["COUNT(*)"] = $query_obj->result[0]["count"];
			
#			$i = 0;
#			foreach($query_obj->result as $result) {
#				foreach($result as $k => $v) {
#					if(gettype($v) == "resource") {
#						$query_obj->result[$i][$k] = stream_get_contents($v);
#					}
#				}
#				$i++;
#			}
		}
		elseif($query_obj->type == WF_INDEX) {
			// ..
		}
	}
	
	public function get_configuration() {
		$dsn .= "dbname=".$dbconf["dbname"].';';
		$dsn .= "host=".$dbconf["host"];
		$user = $dbconf["user"];
		$pass = $dbconf["pass"];
			
		$ret = array(
			"dbhost" => array(
				"default" => "localhost",
				"size" => "20",
				"descript" => "Host du serveur PgSQL"
			),
			"dbport" => array(
				"default" => "3306",
				"size" => "6",
				"descript" => "Port du serveur PgSQL"
			),
			"dbname" => array(
				"default" => "binarysec",
				"size" => "15",
				"descript" => "Nom de la base de donnée"
			),
			"dbuser" => array(
				"default" => "",
				"size" => "15",
				"descript" => "Nom d'utilisateur"
			),
			"dbpass" => array(
				"default" => "",
				"size" => "15",
				"descript" => "Mot de passe"
			)
		);
		
		return($ret);
	}
	
	public function test_configuration($dbconf) {
		try {
			$dsn  = "pgsql:";
			$dsn .= "dbname=".$dbconf["dbname"];
			if($dbconf["dbhost"])
				$dsn .= ";host=".$dbconf["dbhost"];
			if($dbconf["dbport"])
				$dsn .= ";port=".$dbconf["dbport"];

			$user = $dbconf["dbuser"];
			$pass = $dbconf["dbpass"];
			$this->hdl = new PDO($dsn, $user, $pass);
		}
		catch (PDOException $e) {
			return($e->getMessage());
		}

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
			return("SERIAL PRIMARY KEY");
		
		switch($item & 0xF0) {
			case WF_VARCHAR :
				return("VARCHAR(255) NULL");
			case WF_SMALLINT :
				return("SMALLINT NULL");
			case WF_INT :
			case WF_TIME :
				return("INT NULL");
			case WF_BIGINT :
				return("BIGINT NULL");
			case WF_FLOAT :
				return("FLOAT NULL");
			case WF_DATA :
				return("TEXT");
		}
	}

	private function create_table($name, $struct) {
		$pri_list = array();
		
		$query = 'CREATE TABLE "'.$name.'" (';
		$vir = FALSE;
		foreach($struct as $k => $v) {
			if($v & WF_PRIMARY)
				$pri_list[] = $k;
					
			if($vir == TRUE) $query .= ",";
			$query .= '"'.$k.'" '.$this->get_struct_type($v);
			$vir = TRUE;
		}
		
		$query .= ")";
		
		$this->sql_query($query);
	}
	
	private function rename_table($old, $new) {
		$cvar = "core_db_pdo_pgsql_table_$name";
		$query = 'ALTER TABLE "'.$old.'" RENAME TO "'.$new.'"';
		$this->sql_query($query);
		
		/* remove the cache */
		$cvar = "core_db_pdo_pgsql_table_$old";
		$this->a_core_cacher->delete($cvar);
		$cvar = "core_db_pdo_pgsql_zone_$old";
		$this->a_core_cacher->delete($cvar);
		
	}
	
	private function drop_table($name) {
		$query = 'DROP TABLE '.$name;
		$this->sql_query($query);
		
		/* remove the cache */
		$cvar = "core_db_pdo_pgsql_table_$name";
		$this->a_core_cacher->delete($cvar);
		$cvar = "core_db_pdo_pgsql_zone_$name";
		$this->a_core_cacher->delete($cvar);
	}
	
	private function table_exists($name) {
		$cvar = "core_db_pdo_pgsql_table_$name";
		
		/* check if possible to get data from the cache */
		if(($res = $this->a_core_cacher->get($cvar)) != NULL)
			return($res);
		
		
		$q = new core_db_select("pg_tables");
		$where = array();
		$where["tablename"] = $name;
		$q->where($where);
		$this->query($q);
		$res = $q->get_result();
		
		/* push table information */
		$this->a_core_cacher->store(
			$cvar,
			!$res[0] ? FALSE : TRUE
		);
		
		if(!$res[0])
			return(FALSE);
		return(TRUE);
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
		$cvar = "core_db_pdo_pgsql_table_$name";
		$this->a_core_cacher->delete($cvar);
		$cvar = "core_db_pdo_pgsql_zone_$name";
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
		$cvar = "core_db_pdo_pgsql_table_$name";
		$this->a_core_cacher->delete($cvar);
		$cvar = "core_db_pdo_pgsql_zone_$name";
		$this->a_core_cacher->delete($cvar);
	}
	
	public function get_zone($name) {
		/* try to retrieve zone from the cache */
		$cvar = "core_db_pdo_pgsql_zone_$name";
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
