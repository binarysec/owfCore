<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * READ CAREFULLLY
 * 
 * This library was not completed and does not work properly.
 * Do not expect it to run your application at first try.
 * 
 * However it should give you a start if you want to have OpenWF
 * working with pdo_oci.
 * 
 * Do not forget to install pdo_oci PHP module.
 * 
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

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

function array_keys_to_lower(array $arr, $recursive = true) {
	if(count($arr) > 0) {
		foreach($arr as $k => $v) {
			if(is_array($v) && $recursive)
				$arr[$k] = array_keys_to_lower($v);
		}
		$lowerCaseKeys = array_map('strtolower', array_keys($arr));
		$duplicates = array_filter(array_count_values($lowerCaseKeys), create_function('$count', 'return $count > 1;'));
		if (!empty($duplicates)) {
			throw new Exception('duplicate keys found: ' . implode(',', array_keys($duplicates)));
		}
		return array_merge($arr, array_combine($lowerCaseKeys, array_values($arr)));
	}
}

class core_db_pdo_oci extends core_db {
	private $a_core_cacher;
	public $hdl = NULL;
	public $request_c = 0;
	
	public $schema = array();
	
	public function load($dbconf) {
		/* open oracle database */
		try {
			$tns = "
				(DESCRIPTION =
					(ADDRESS = (PROTOCOL = TCP)(HOST = ".$dbconf["host"].")(PORT = 1521))
					(CONNECT_DATA =
						(SERVER = DEDICATED)
						(SERVICE_NAME = ".$dbconf["service"].")
					)
				)
			";
			$dsn = "oci:dbname=".$tns;
			/*if(array_key_exists("host",$dbconf))
				$dsn .= ";host=".$dbconf["host"];
			if(array_key_exists("port",$dbconf))
				$dsn .= ";port=".$dbconf["port"];
			if(array_key_exists("unix_socket",$dbconf))
				$dsn .= ";unix_socket=".$dbconf["unix_socket"];*/
				
			$user = $dbconf["user"];
			$pass = $dbconf["pass"];
			
			$this->hdl = new PDO($dsn, $user, $pass/*, array(PDO::ATTR_PERSISTENT => true)*/);
		} 
		catch (PDOException $e) {
			print "Oracle: " . $e->getMessage() . "<br/>";
			die();
		}
		
		/* get cacher */
		$this->a_core_cacher = $this->wf->core_cacher();

		return(TRUE);
	}
	
	public function get_last_insert_id($seq = null) {
		//return($this->hdl->lastInsertId(strtoupper($seq)));
		$ret = $this->fetch_result($this->sql_query("SELECT ".strtoupper($seq).".CURRVAL FROM dual"));
		
		return $ret[0]["CURRVAL"];
	}
	
	public function get_request_counter() {
		return($this->request_c);
	}
	
	public function get_driver_name() {
		return("OCI");
	}
	
	public function get_driver_banner() {
		return("OCI/".$this->hdl->getAttribute(PDO::ATTR_SERVER_VERSION));
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Register a new DB zone
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function register_zone($name, $description, $struct) {
		if(!$this->table_exists($name))
			$this->create_table($name, $struct);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Unregister a DB zone
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function unregister_zone($name) {
		$this->drop_table($name);
		return(TRUE);
	}
	
	/* backyard functions :] */
	private function sql_query($query) {
		
		var_dump($query);echo "<br/>";
		
		$q = $this->hdl->prepare($query/*, array(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true)*/);
		if(!$q) {
			$er = $this->hdl->errorInfo();
			echo "* Oracle->prepare()#$er[0]: $er[2]\n";
			echo "*            \_ SQL request: $query\n";
			die();
		}
		
		$q->execute();
		$er = $q->errorInfo();
		if($er[0] != 0) {
			$er = $this->hdl->errorInfo();
			echo "* Oracle->prepare()#$er[0]: $er[2]\n";
			echo "*            \_ SQL request: $query\n";
			die();
		}

		$this->request_c++;
		
		return($q);
	}
	
	private function fetch_result($query_object) {
		$res = array_keys_to_lower($query_object->fetchAll(/*PDO::FETCH_NAMED*/));
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
			
			/* check for where/and condition */
			$where = NULL;
			if($query_obj->where != NULL) {
				foreach($query_obj->where as $k => $sv) {
					$v = $this->safe_input($sv);
					if(!$where)
						$where .= "WHERE ".$k." = $v";
					else
						$where .= " AND ".$k." = $v";
				}
			}
			
			/* check for order */
			$order = NULL;
			if($query_obj->order != NULL) {
				foreach($query_obj->order as $k => $v) {
					if(!$order) {
						$order = "ORDER BY '".$k."'";
						if($v == WF_ASC)
							$order .= " ASC";
						else
							$order .= " DESC";
					}
					else {
						$order .= ", '".$k."'";
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
						$group = "GROUP BY '".$k."'";
					}
					else {
						$group .= ", '".$k."'";
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
			$res = $this->sql_query($query);
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
					$fields .= $query_obj->fields[$a];
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
							$fields .= $var;
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
					$as .= " ";
					$as .= $query_obj->as[$a]["A"];
				}
			}
			else {
				$as = $query_obj->zone;
			}
			
			/* condition matrix */
			$cond = NULL;
			$atom = 0;
			if(count($query_obj->cond_matrix) > 0)
				$cond .= " WHERE";
				
			foreach($query_obj->cond_matrix as $k) {
				switch($k[0]) {
					case 1:
						switch($atom) {
							case 1: $cond .= ' OR '; break;
							case 2: case 3: $cond .= ' AND '; break;
						}
						$atom = 0;
						$cond .= '(';
						break;
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
							$k[3]
						);
						break;
				}
			}
			
			/* check for order */
			$order = NULL;
			if(is_array($query_obj->order)) {
				foreach($query_obj->order as $k => $v) {
					if(!$order) {
						$order = "ORDER BY '".$k."'";
						if($v == WF_ASC)
							$order .= " ASC";
						else
							$order .= " DESC";
					}
					else {
						$order .= ", '".$k."'";
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
						$group = "GROUP BY '".$k."'";
					}
					else {
						$group .= ", '".$k."'";
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
			
			if($query_obj->req_fct & WF_REQ_FCT_COUNT){
				if($query_obj->fields != NULL)
					$fields .= ", COUNT(*) as count_etoile";
				else
					$fields = "COUNT(*)";
			}
			if($query_obj->req_fct & WF_REQ_FCT_DISTINCT)
				$select = "SELECT DISTINCT";
			else
				$select = "SELECT";
				
			$query = "$select $fields FROM $as $cond $group $order $limit $offset";
			
			$res = $this->sql_query($query);
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
					$key = "".$k."";
					$val .= $v;
				}
				else {
					$key .= ", ".$k."";
					$val .= ", ".$v;
				}
				array_push($prepare_value, $v);
			}
			$query = "INSERT INTO ".$query_obj->zone." ($key) VALUES ($val)";
			$this->sql_query($query);
		}
		/* Insert query with ID */
		else if($query_obj->type == WF_INSERT_ID) {
			/* fill fields */
			$key = NULL;
			$val = NULL;
			foreach($query_obj->arr as $k => $sv) {
				$v = $this->safe_input($sv);
				if(!$key) {
					$key = $k;
					$val .= $v;
				}
				else {
					$key .= ", ".$k;
					$val .= ", $v";
				}
			}

			/* insertion */
			$query =
				"INSERT INTO ".
				$query_obj->zone." ($key) VALUES ($val)";
			$this->sql_query($query);
			
			$id = $this->get_last_insert_id($query_obj->zone."_".$query_obj->id."_seq");
			
			
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
			/* fill fields */
			$set = NULL;
			foreach($query_obj->arr as $k => $sv) {
				$v = $this->safe_input($sv);
				if(!$set)
					$set .= $k." = $v";
				else
					$set .= ", ".$k." = $v";
			}
		
			/* fill where condition */
			$where = NULL;
			foreach($query_obj->where as $k => $sv) {
				$v = $this->safe_input($sv);
				if(!$where)
					$where .= "WHERE ".$k." = $v";
				else
					$where .= " AND ".$k." = $v";
			}
			
			/* prepare and exec the query */
			$query = "UPDATE ".$query_obj->zone." SET $set $where";
			$this->sql_query($query);
		}
		/* delete query */
		else if($query_obj->type == WF_DELETE) {
			/* check for where/and condition */
			$where = NULL;
			if($query_obj->where != NULL) {
				foreach($query_obj->where as $k => $sv) {
					$v = $this->safe_input($sv);
					if(!$where)
						$where .= "WHERE '".$k."' = ?";
					else
						$where .= " AND '".$k."' = ?";
				}
			}
			
			/* prepare and exec the query */
			$query = "DELETE FROM ".$query_obj->zone." $where";
			$this->sql_query($query);
		}
		/* advanced delete query */
		else if($query_obj->type == WF_ADV_DELETE) {
			/* condition matrix */
			$cond = NULL;
			$atom = 0;
			if(count($query_obj->cond_matrix) > 0)
				$cond .= " WHERE";
			
						foreach($query_obj->cond_matrix as $k) {
				switch($k[0]) {
					case 1:
						switch($atom) {
							case 1: $cond .= ' OR '; break;
							case 2: case 3: $cond .= ' AND '; break;
						}
						$atom = 0;
						$cond .= '(';
						break;
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
							$k[3]
						);
						break;
				}
			}
	
			/* prepare and exec the query */
			$query = "DELETE FROM ".$query_obj->zone." $cond";
			$this->sql_query($query);
		}
	}
			
	public function get_configuration() {
		$ret = array(
			"dbhost" => array(
				"default" => "localhost",
				"size" => "20",
				"descript" => "Host du serveur Oracle"
			),
			"dbport" => array(
				"default" => "1521",
				"size" => "6",
				"descript" => "Port du serveur Oracle"
			),
			"dbname" => array(
				"default" => "XE",
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
	
	public function test_configuration($dbconf) {return true;}
	
	
	
	
	
	
	
	private function get_query_var($var, $sign, $sval) {
		$cond = NULL;
		$val = $this->safe_input($sval);
		switch($sign) {
			case '=':
				$cond = "$var = $val";
				break;
			case '==':
				$cond = "$var = $val";
				break;
			case '~=':
				$cond = "$var LIKE $val";
				break;
			case '>':
				$cond = "$var > $val";
				break;
			case '<':
				$cond = "$var < $val";
				break;
			case '>=':
				$cond = "$var >= $val";
				break;
			case '<=':
				$cond = "$var <= $val";
				break;
			case '!=':
				$cond = "$var <> $val";
				break;
			case '!':
				$cond = "$var IS NULL";
				break;
			case '!!':
				$cond = "$var IS NOT NULL";
				break;
		}
		
		return($cond);
	}
	
	private function get_struct_type($item) {
		$ret = "";
		switch($item & 0xF0) {
			case WF_VARCHAR :
				$ret .= "VARCHAR(255)";break;
			case WF_SMALLINT :
				$ret .= "SMALLINT NULL";break;
			case WF_INT :
			case WF_TIME :
			case WF_BIGINT :
				$ret .= "NUMBER NULL";break;
			case WF_FLOAT :
				$ret .= "FLOAT NULL";break;
			case WF_DATA :
				$ret .= "BLOB";break;
		}
		
		return $ret;
	}
	
	private function create_table($name, $struct) {
		$pri_list = array();
		$autoinc_list = array();
		
		$query = "CREATE TABLE ".$name." (";
		$vir = FALSE;
		foreach($struct as $k => $v) {
			if($v & WF_PRIMARY)
				$pri_list[$k] = $v;
			
			if($v & WF_AUTOINC)
				$autoinc_list[$k] = $v;
					
			if($vir == TRUE) $query .= ",";
			$query .= $k." ".$this->get_struct_type($v);
			$vir = TRUE;
		}
		
		/* preparation des clés */
		if(count($pri_list) > 0) {
			$query .= ", PRIMARY KEY ( ";
			$a = 0;
			foreach($pri_list as $k => $v) {
				if($a > 0)
					$query .= ",";
				$query .= "$k";
				
				if(($v & 0xF0) == WF_DATA)
					$query .= "(500)";
				
				$a++;
			}
			$query .= ")";
		}
		
		/* ajoute les clés */
		$query .= ")";
		$this->sql_query($query);
		
		/* Créé les séquences et les triggers pour les auto increment */
		if(count($autoinc_list) > 0) {
			foreach($pri_list as $k => $v) {
				$seq_name = $name."_".$k."_seq";
				$trig_name = $name."_".$k."_trig";
				
				$query = "CREATE SEQUENCE $seq_name START WITH 1 INCREMENT BY 1 nomaxvalue";
				$this->sql_query($query);
				
				$query = "CREATE TRIGGER $trig_name BEFORE INSERT ON $name FOR EACH ROW begin SELECT $seq_name.nextval INTO :new.$k FROM dual; end;";
				$this->sql_query($query);
			}
		}
	}
	
	private function drop_table($name) {
		$query = "DROP TABLE ".$name;
		$this->sql_query($query);
		
		/* remove the cache */
		$cvar = "core_db_pdo_oracle_table_$name";
		$this->a_core_cacher->delete($cvar);
		$cvar = "core_db_pdo_oracle_zone_$name";
		$this->a_core_cacher->delete($cvar);
	}
	
	private function table_exists($name) {
		$cvar = "core_db_pdo_oracle_table_$name";
		
		/* check if possible to get data from the cache */
		if(($res = $this->a_core_cacher->get($cvar)) != NULL)
			return($res);
			
		$query = "SELECT table_name FROM user_tables WHERE table_name = '".strtoupper($name)."'";
		$res = $this->fetch_result($this->sql_query($query));
		
		$this->a_core_cacher->store($cvar, count($res));
		
		return count($res);
	}
	
	private function safe_input($i) {
		$d = !is_numeric($i) ? "'" : "";
		return($d.stripslashes($i).$d);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Moar
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	
	public function get_zone($name) {
		/* try to retrieve zone from the cache */
		$cvar = "core_db_pdo_mysql_zone_$name";
		if(($res = $this->a_core_cacher->get($cvar)) != NULL)
			return($res);

		$query = "SELECT column_name FROM user_tab_cols WHERE table_name = '".strtoupper($name)."'";
		$ret = $this->fetch_result($this->sql_query($query));
		$res = array();
		
		foreach($ret as $k => $v) {
			$res[] = $v["column_name"];
		}
		
		return($res);
	}
	
	public function is_usable() {
		$d = PDO::getAvailableDrivers();
		foreach($d as $v) {
			if($v == "oci")
				return(TRUE);
		}
		return(FALSE);
	}
}