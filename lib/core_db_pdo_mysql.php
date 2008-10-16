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

function core_gettype($value) {
	$type = WF_T_INTEGER;
	
	for($a=0; $a<strlen($value); $a++) {
		if(
			ord($value[$a]) >= 0x30 &&
			ord($value[$a]) <= 0x39) {
		}
		else if(
			$value[$a] == '.' &&
			$type == WF_T_INTEGER) {
			$type = WF_T_DOUBLE;
		}
		else {
			return(WF_T_STRING);
		}
	}
	
	return($type);
}

class core_db_pdo_mysql extends core_db {
	private $a_core_cacher;
	var $hdl = NULL;
	var $request_c = 0;
	
	var $schema = array();
	
	public function load($dbconf) {
		/* open mysql database */
		try {
			$dsn  = "mysql:";

			$dsn .= "dbname=".$dbconf["dbname"];
			if($dbconf["host"])
				$dsn .= ";host=".$dbconf["host"];
			if($dbconf["port"])
				$dsn .= ";port=".$dbconf["port"];
			if($dbconf["unix_socket"])
				$dsn .= ";unix_socket=".$dbconf["unix_socket"];
				
			$user = $dbconf["user"];
			$pass = $dbconf["pass"];
			
			$this->hdl = new PDO($dsn, $user, $pass);	
		} 
		catch (PDOException $e) {
			print "MySQL: " . $e->getMessage() . "<br/>";
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
			if($v == "mysql")
				return(TRUE);
		}
		return(FALSE);
	}
	
	public function get_driver_name() {
		return("MySQL");
	}
	
	public function get_driver_banner() {
		return("MySQL/".$this->hdl->getAttribute(PDO::ATTR_SERVER_VERSION));
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
		if(!$res[0]) {
			$this->create_table($name, $struct);
			$this->create_zone($name, $struct);
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
				$query = "ALTER TABLE `$name` ADD `$k` $type ;";
				$this->sql_query($query);
				$change = TRUE;
			}
		}
		
		/* vérifi les élément à enlever */
		foreach($all as $k => $v) {
			if(!$struct[$k]) {
				$query = "ALTER TABLE `$name` DROP `$k` ;";
				$this->sql_query($query);
				$change = TRUE;
			}

		}

		if($change == TRUE) {
			$this->drop_zone($name);
			$this->create_zone($name, $struct);
		}
	}
	
	private function info($data) {
// 		if(count($_REQUEST) == 0) 
// 			echo $data;
	}
	
	/* backyard functions :] */
	private function sql_query($query, $values=NULL) {
		$q = $this->hdl->prepare(
			$query, 
			array(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true)
		);
		if(!$q) {
			$er = $this->hdl->errorInfo();
			echo "* MySQL->prepare()#$er[0]: $er[2]\n";
			echo "*            \_ SQL request: $query\n";
			die();
		}

		$q->execute($values);
		$er = $q->errorInfo();
		if($er[0] != 0) {
			$er = $this->hdl->errorInfo();
			echo "* MySQL->prepare()#$er[0]: $er[2]\n";
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
		/* Select query */
		if($query_obj->type == WF_SELECT) {
			/* check for fields */
			$fields = NULL;
			if($query_obj->fields != NULL) {
				for($a=0; $a<count($query_obj->fields); $a++) {
					if($fields != NULL) 
						$fields .= ",";
					$fields .= $query_obj->fields[$a];
				}
			}
			else
				$fields = "*";
			
			$prepare_value = array();
			
			/* check for where/and condition */
			$where = NULL;
			if($query_obj->where != NULL) {
				foreach($query_obj->where as $k => $sv) {
					$v = $this->safe_input($sv);
					if(!$where) {
						if(core_gettype($v) == WF_T_STRING)
							$where .= 'WHERE `'.$k.'` LIKE ?';
						else
							$where .= 'WHERE `'.$k.'` = ?';
						
					}
					else {
						if(core_gettype($v) == WF_T_STRING)
							$where .= ' AND `'.$k.'` LIKE ?';
						else
							$where .= ' AND `'.$k.'` = ?';
					}
					
					array_push($prepare_value, $v);
				}
			}
			
			/* check for order */
			$order = NULL;
			if($query_obj->order != NULL) {
				foreach($query_obj->order as $k => $v) {
					if(!$order) {
						$order = 'ORDER BY `'.$k.'`';
						if($v == WF_ASC)
							$order .= ' ASC';
						else
							$order .= ' DESC';
					}
					else {
						$order .= ', `'.$k.'`';
						if($v == WF_ASC)
							$order .= ' ASC';
						else
							$order .= ' DESC';
					}
				}
			}

			/* check for group */
			$group = NULL;
			if($query_obj->group != NULL) {
				foreach($query_obj->group as $k) {
					if(!$group) {
						$group = 'GROUP BY `'.$k.'`';
					}
					else {
						$group .= ', `'.$k.'`';
					}
				}
			}
			
			/* check for limit and offset */
			$limit = NULL;
			$offset = NULL;
			if($query_obj->limit > -1) {
				$limit = "LIMIT ".intval($query_obj->limit);
				if($query_obj->offset > -1)
					$offset = "OFFSET ".intval($query_obj->offset);
			}
			
			$query = 
				"SELECT $fields FROM ".
				$query_obj->zone.
				" $where $group $order $limit $offset";
			$res = $this->sql_query($query, $prepare_value);
			$query_obj->result = $this->fetch_result($res);
		}
		/* Select query */
		if($query_obj->type == WF_ADV_SELECT) {
			/* construit les fields */
			$fields = NULL;
			if($query_obj->fields != NULL) {
				for($a=0; $a<count($query_obj->fields); $a++) {
					if($fields != NULL) 
						$fields .= ",";
					$fields .= $query_obj->fields[$a].
						' AS "'.
						$query_obj->fields[$a].
						'"'
					;
				}
			}
			else {
				/*
				 * Si il y a plus d'un alias alors il faut aligner les structures
				 */
				if(count($query_obj->as) > 1) {
					for($a=0; $a<count($query_obj->as); $a++) {
						$alias = $query_obj->as[$a]["A"];
						$tab = $query_obj->as[$a]["T"];
						$sch = $this->schema[$tab];
// 						echo $tab."\n";
						foreach($sch as $k => $v) {
							if($fields != NULL) 
								$fields .= ",";
							$var = "$alias.$k";
							$fields .= $var.' AS "'.$var.'"';
						}
					}
				}
				/* check request function */
				else {
					$fields = "*";
				}
			}
			/* ensuite les AS si yen a */
			
			$as = NULL;
			if(count($query_obj->as) > 0) {
				for($a=0; $a<count($query_obj->as); $a++) {
					if($as != NULL) 
						$as .= ",";
					$as .= $query_obj->as[$a]["T"];
					$as .= " AS ";
					$as .= $query_obj->as[$a]["A"];
				}
			}
			else {
				$as = $query_obj->zone;
			}
			
			$prepare_value = array();
			
			/* condition matrix */
			$cond = NULL;
			$atom = 0;
			if(count($query_obj->cond_matrix) > 0)
				$cond .= " WHERE";
				
			foreach($query_obj->cond_matrix as $k) {
				switch($k[0]) {
					case 1: $cond .= '('; break;
					case 2: $cond .= ')'; break;
					case 3: $atom = 1; break;
					case 4: $atom = 2; break;
					case 5:
						switch($atom) {
							case 0: $cond .= ' '; $atom = 3; break;
							case 1: $cond .= ' OR '; $atom = 3; break;
							case 2: $cond .= ' AND '; $atom = 3; break;
							case 3: $cond .= ' AND '; break;
						}
						$cond .= $this->get_query_var(
							$k[1], 
							$k[2], 
							$k[3], 
							&$prepare_value
						);
						break;
				}
			}
			
			/* check for order */
			$order = NULL;
			if($query_obj->order != NULL) {
				foreach($query_obj->order as $k => $v) {
					if(!$order) {
						$order = 'ORDER BY `'.$k.'`';
						if($v == WF_ASC)
							$order .= " ASC";
						else
							$order .= " DESC";
					}
					else {
						$order .= ', `'.$k.'`';
						if($v == WF_ASC)
							$order .= " ASC";
						else
							$order .= " DESC";
					}
				}
			}

			/* check for group */
			$group = NULL;
			if($query_obj->group != NULL) {
				foreach($query_obj->group as $k) {
					if(!$group) {
						$group = 'GROUP BY `'.$k.'`';
					}
					else {
						$group .= ', `'.$k.'`';
					}
				}
			}
			
			/* check for limit and offset */
			$limit = NULL;
			$offset = NULL;
			if($query_obj->limit > -1) {
				$limit = "LIMIT ".intval($query_obj->limit);
				if($query_obj->offset > -1)
					$offset = "OFFSET ".intval($query_obj->offset);
			}
			
			if($query_obj->req_fct == WF_REQ_FCT_COUNT)
				$fields = "COUNT(*)";
				
			$query = "SELECT $fields FROM $as $cond $group $order $limit $offset";
			
			$res = $this->sql_query($query, $prepare_value);
			$query_obj->result = $this->fetch_result($res);
		}
		/* Insert query */
		else if($query_obj->type == WF_INSERT) {
			$prepare_value = array();
			
			/* fill fields */
			$key = NULL;
			$val = NULL;
			foreach($query_obj->arr as $k => $sv) {
				$v = $this->safe_input($sv);
				if(!$key) {
					$key = '`'.$k.'`';
					$val .= '?';
				}
				else {
					$key .= ', `'.$k.'`';
					$val .= ', ?';
				}
				array_push($prepare_value, $v);
			}
			$query = "INSERT INTO ".$query_obj->zone." ($key) VALUES ($val);";
			$this->sql_query($query, $prepare_value);
		}
		/* Insert query with ID */
		else if($query_obj->type == WF_INSERT_ID) {
			$prepare_value = array();
			
			/* fill fields */
			$key = NULL;
			$val = NULL;
			foreach($query_obj->arr as $k => $sv) {
				$v = $this->safe_input($sv);
				if(!$key) {
					$key = '`'.$k.'`';
					$val .= '?';
				}
				else {
					$key .= ', `'.$k.'`';
					$val .= ', ?';
				}
				array_push($prepare_value, $v);
			}

			/* insertion */
			$query =
				"INSERT INTO ".
				$query_obj->zone." ($key) VALUES ($val);";
			$this->sql_query($query, $prepare_value);
			
			$id = $this->hdl->lastInsertId();
			
			
			/* chope toute les infos */
			$q = new core_db_select($query_obj->zone);
			$where = array();
			$where[$query_obj->id] = $id;
			$q->where($where);
			$this->query($q);

			$query_obj->result = $q->get_result();
			$query_obj->result = $query_obj->result[0];
			
		}
		/* update query */
		else if($query_obj->type == WF_UPDATE) {
			$prepare_value = array();
			
			/* fill fields */
			$set = NULL;
			foreach($query_obj->arr as $k => $sv) {
				$v = $this->safe_input($sv);
				if(!$set)
					$set .= '`'.$k.'` = ?';
				else
					$set .= ', `'.$k.'` = ?';
				array_push($prepare_value, $v);
			}
		
			/* fill where condition */
			$where = NULL;
			foreach($query_obj->where as $k => $sv) {
				$v = $this->safe_input($sv);
				if(!$where) {
					if(core_gettype($v) == WF_T_STRING)
						$where .= 'WHERE `'.$k.'` LIKE ?';
					else
						$where .= 'WHERE `'.$k.'` = ?';
				}
				else {
					if(core_gettype($v) == WF_T_STRING)
						$where .= ' AND `'.$k.'` LIKE ?';
					else
						$where .= ' AND `'.$k.'` = ?';
				}
				array_push($prepare_value, $v);
			}
			
			/* prepare and exec the query */
			$query = "UPDATE ".$query_obj->zone." SET $set $where";
			$this->sql_query($query, $prepare_value);
		}
		/* delete query */
		else if($query_obj->type == WF_DELETE) {
			$prepare_value = array();
			/* check for where/and condition */
			$where = NULL;
			if($query_obj->where != NULL) {
				foreach($query_obj->where as $k => $sv) {
					$v = $this->safe_input($sv);
					if(!$where) {
						if(core_gettype($v) == WF_T_STRING)
							$where .= 'WHERE `'.$k.'` LIKE ?';
						else
							$where .= 'WHERE `'.$k.'` = ?';
					}
					else {
						if(core_gettype($v) == WF_T_STRING)
							$where .= ' AND `'.$k.'` LIKE ?';
						else
							$where .= ' AND `'.$k.'` = ?';
					}
					array_push($prepare_value, $v);
				}
			}
			
			/* prepare and exec the query */
			$query = "DELETE FROM ".$query_obj->zone." $where";
			$this->sql_query($query, $prepare_value);
		}
		/* advanced delete query */
		else if($query_obj->type == WF_ADV_DELETE) {
			$prepare_value = array();

			/* condition matrix */
			$cond = NULL;
			$atom = 0;
			if(count($query_obj->cond_matrix) > 0)
				$cond .= " WHERE";
				
			foreach($query_obj->cond_matrix as $k) {
				switch($k[0]) {
					case 1: $cond .= '('; break;
					case 2: $cond .= ')'; break;
					case 3: $atom = 1; break;
					case 4: $atom = 2; break;
					case 5:
						switch($atom) {
							case 0: $cond .= ' '; $atom = 3; break;
							case 1: $cond .= ' OR '; $atom = 3; break;
							case 2: $cond .= ' AND '; $atom = 3; break;
							case 3: $cond .= ' AND '; break;
						}
						$cond .= $this->get_query_var(
							$k[1], 
							$k[2], 
							$k[3], 
							&$prepare_value
						);
						break;
				}
			}
			
			/* prepare and exec the query */
			$query = "DELETE FROM ".$query_obj->zone." $cond";
			$this->sql_query($query, $prepare_value);
		}
		
		/* Select distinct query */
		if($query_obj->type == WF_SELECT_DISTINCT) {
			/* check for fields */
			$fields = NULL;
			if($query_obj->fields != NULL) {
				for($a=0; $a<count($query_obj->fields); $a++) {
					if($fields != NULL) 
						$fields .= ",";
					$fields .= $query_obj->fields[$a];
				}
			}
			else
				$fields = "*";
			
			/* check for order */
			$order = NULL;
			if($query_obj->order != NULL) {
				foreach($query_obj->order as $k => $v) {
					if(!$order) {
						$order = 'ORDER BY `'.$k.'`';
						if($v == WF_ASC)
							$order .= ' ASC';
						else
							$order .= ' DESC';
					}
					else {
						$order .= ', `'.$k.'`';
						if($v == WF_ASC)
							$order .= ' ASC';
						else
							$order .= ' DESC';
					}
				}
			}

			/* check for group */
			$group = NULL;
			if($query_obj->group != NULL) {
				foreach($query_obj->group as $k) {
					if(!$group) {
						$group = 'GROUP BY `'.$k.'`';
					}
					else {
						$group .= ', `'.$k.'`';
					}
				}
			}
			
			/* check for limit and offset */
			$limit = NULL;
			$offset = NULL;
			if($query_obj->limit > -1) {
				$limit = "LIMIT ".intval($query_obj->limit);
				if($query_obj->offset > -1)
					$offset = "OFFSET ".intval($query_obj->offset);
			}
			
			$query = 
				"SELECT DISTINCT $fields FROM ".
				$query_obj->zone.
				" $group $order $limit $offset";
			
			$res = $this->sql_query($query);
			$query_obj->result = $this->fetch_result($res);
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
				"descript" => "Host du serveur MySQL"
			),
			"dbport" => array(
				"default" => "3306",
				"size" => "6",
				"descript" => "Port du serveur MySQL"
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
			$dsn  = "mysql:";
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
	
	private function get_query_var($var, $sign, $sval, $prepare_values) {
		$cond = NULL;
		$val = $this->safe_input($sval);
		switch($sign) {
			case '=':
				if(core_gettype($val) == WF_T_STRING)
					$cond = "$var LIKE ?";
				else
					$cond = "$var = ?";
				array_push($prepare_values, $val);
				break;
			case '==':
				$cond = "$var = $val";
				break;
			case '>':
				$cond = "$var > ?";
				array_push($prepare_values, $val);
				break;
			case '<':
				$cond = "$var < ?";
				array_push($prepare_values, $val);
				break;
			case '>=':
				$cond = "$var >= ?";
				array_push($prepare_values, $val);
				break;
			case '<=':
				$cond = "$var <= ?";
				array_push($prepare_values, $val);
				break;
			case '!=' :
				$cond = "$var <> ?";
				array_push($prepare_values,$val);
				break;
		}
		
		return($cond);
	}
	
	/*
	get the wrapped type 
	*/
	private function get_struct_type($item) {
		switch($item) {
			case WF_VARCHAR:
				return("VARCHAR(255) NULL");
			case WF_SMALLINT:
				return("SMALLINT NULL");
			case WF_INT:
				return("INT NULL");
			case WF_FLOAT:
				return("FLOAT NULL");
			case WF_TIME:
				return("INT NULL");
			case WF_DATA:
				return("BLOB");
			case WF_PRI:
				return("INT NULL AUTO_INCREMENT");
		}
	}

	private function create_table($name, $struct) {
		$pri_list = array();
		
		$query = 'CREATE TABLE `'.$name.'` (';
		$vir = FALSE;
		foreach($struct as $k => $v) {
			if($v == WF_PRI)
				$pri_list[] = $k;
					
			if($vir == TRUE) $query .= ",";
			$query .= '`'.$k.'` '.$this->get_struct_type($v);
			$vir = TRUE;
		}
		
		/* preparation des clés */
		if(count($pri_list) > 0) {
			$query .= ", PRIMARY KEY ( ";
			for($a=0; $a<count($pri_list); $a++) {
				$v = &$pri_list[$a];
			
				if($a > 0)
					$query .= ",";
					
				$query .= "`$v`";
			}
			$query .= ")";
		}
		
		/* ajoute les clés */
		$query .= ")";
		
		$this->sql_query($query);
	}
	
	private function rename_table($old, $new) {
		$cvar = "core_db_pdo_mysql_table_$name";
		$query = 'ALTER TABLE "'.$old.'" RENAME TO "'.$new.'"';
		$this->sql_query($query);
		
		/* remove the cache */
		$cvar = "core_db_pdo_mysql_table_$old";
		$this->a_core_cacher->delete($cvar);
		$cvar = "core_db_pdo_mysql_zone_$old";
		$this->a_core_cacher->delete($cvar);
		
	}
	
	private function drop_table($name) {
		$query = 'DROP TABLE `'.$name.'`';
		$this->sql_query($query);
		
		/* remove the cache */
		$cvar = "core_db_pdo_mysql_table_$name";
		$this->a_core_cacher->delete($cvar);
		$cvar = "core_db_pdo_mysql_zone_$name";
		$this->a_core_cacher->delete($cvar);
	}
	
	private function table_exists($name) {
		$cvar = "core_db_pdo_mysql_table_$name";
		
		/* check if possible to get data from the cache */
		if(($res = $this->a_core_cacher->get($cvar)) != NULL)
			return($res);
			
		$query = 'SHOW TABLES LIKE "'.$name.'"';
		$res = $this->sql_query($query);
		
		/* push table information */
		$this->a_core_cacher->store(
			$cvar,
			!$res->rowCount() ? FALSE : TRUE
		);
		
		if(!$res->rowCount())
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
		$cvar = "core_db_pdo_mysql_table_$name";
		$this->a_core_cacher->delete($cvar);
		$cvar = "core_db_pdo_mysql_zone_$name";
		$this->a_core_cacher->delete($cvar);
	}
	
	private function create_zone($name, $struct) {
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
		$cvar = "core_db_pdo_mysql_table_$name";
		$this->a_core_cacher->delete($cvar);
		$cvar = "core_db_pdo_mysql_zone_$name";
		$this->a_core_cacher->delete($cvar);
	}
	
	private function get_zone($name) {
		/* try to retrieve zone from the cache */
		$cvar = "core_db_pdo_mysql_zone_$name";
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
	
	/* gestion des transactions */
	public function trans_begin($type=WF_TRANS_DEFERRED, $name=NULL) {
		
		/* type de transaction */
		switch($type) {
// 			case WF_TRANS_IMMEDIAT:
// 				$type_str = "IMMEDIAT"; break;
// 			
// 			case WF_TRANS_DEFERRED:
// 				$type_str = "DEFERRED"; break;
// 				
// 			case WF_TRANS_EXCLUSIF:
// 				$type_str = "EXCLUSIF"; break;
				
			default:
				$type_str = ""; break;
		}
		/* il y a un nom */
		$name_str = $name == NULL ? "" : $name;
		
		/* req sql */
		$query = "BEGIN $type_str TRANSACTION";
// 		$this->sql_query($query);
		
		return(TRUE);
	}
	
	public function trans_cancel($name=NULL) {
		/* req sql */
// 		$this->sql_query("ROLLBACK");
	}
	
	public function trans_commit($name=NULL) {
		/* req sql */
// 		$this->sql_query("COMMIT");
	}
	
	private function safe_input($i) {
		return(stripslashes($i));
	}
	
}