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

class core_db_pdo_mysql extends core_db {
	private $a_core_cacher;
	public $hdl = NULL;
	public $request_c = 0;
	
	public $schema = array();
	
	public function load($dbconf) {
		/* open mysql database */
		try {
			$dsn  = "mysql:";

			$dsn .= "dbname=".$dbconf["dbname"];
			if(array_key_exists("host",$dbconf))
				$dsn .= ";host=".$dbconf["host"];
			if(array_key_exists("port",$dbconf))
				$dsn .= ";port=".$dbconf["port"];
			if(array_key_exists("unix_socket",$dbconf))
				$dsn .= ";unix_socket=".$dbconf["unix_socket"];
				
			$user = $dbconf["user"];
			$pass = $dbconf["pass"];
			
			$this->hdl = new PDO(
				$dsn, $user, $pass, 
				array(
   					PDO::ATTR_PERSISTENT => true
				)
			);
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
		$info = array();
		$changed = false;
		
		/* if table exists or not */
		if($this->table_exists($name)) {
			if(!isset($res[0])) {
				foreach($this->get_table_struct($name) as $k => $v) {
					$info[] = array(
						"b.name" => $k,
						"b.type" => $v - ($v & WF_PRIMARY) - ($v & WF_AUTOINC),
					);
				}
				$changed = $this->check_for_data_translation($name, $description, $struct, $info);
			}
			else
				$changed = $this->check_for_data_translation($name, $description, $struct, $res);
		}
		else
			$this->create_table($name, $struct);
		
		/* if zone exists or not */
		if(!isset($res[0]))
			$this->create_zone($name, $struct, $description);
		elseif($changed) {
			$this->drop_zone($name);
			$this->create_zone($name, $struct, $description);
		}
		
		$this->schema[$name] = $struct;
	}
	
	
	private function load_index($zone) {
		$r = array();
		
		$sq = $this->sql_query("SHOW INDEX FROM $zone");
		$sif = $this->fetch_result($sq);
		
		foreach($sif as $index) {
			if(!array_key_exists($index['Key_name'], $r))
				$r[$index['Key_name']] = array();
			
			if(!array_key_exists($index['Column_name'], $r[$index['Key_name']]))
				$r[$index['Key_name']][$index['Column_name']] = !$index['Non_unique'];
		}
		
		return($r);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * 
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	private function manage_index($zone, $name, $struct) {
		$cvar = "core_db_pdo_mysql_manage_index_$zone/$name";
		
		if(($res = $this->a_core_cacher->get($cvar)) != NULL)
			return($res);
		
		$z = $this->get_zone($zone);
		if(!is_array($z[0]))
			return(false);
			
		/* get id */
		$idx = $this->get_index($z[0]['a.id'], $name);

		/* transpose information */
		$all = array();
		foreach($idx as $k => $v)
			$all[$v["colname"]] = isset($v["unique"]) ? $v["unique"] : false;
	
		/* load table indexes */
		$sif = $this->load_index($zone);
		
		$v = array_values($struct);
		$is_unique = array_pop($v);
		$unique = $is_unique ? 'UNIQUE' : '';

		/* check if the index key must be removed */
		foreach($all as $k => $v) {
			if(!array_key_exists($k, $struct) || $v != $is_unique) {
				if(array_key_exists($name, $sif)) {
					$q = "ALTER TABLE `$zone` DROP INDEX `$name`";
					$this->sql_query($q);
					$q = new core_db_delete(
						"zone_index",
						array(
							"zone_id" => $z[0]['a.id'],
							"indexname" => $name
						)
					);
					$this->query($q);
				}
				break;
			}
		}
		
		/* check which one needs to be added */
		$chain_to_add = "ALTER TABLE `$zone` ADD $unique INDEX `$name` (";
		$chain_to_add_vir = false;
		foreach($struct as $k => $v) {
			if(!array_key_exists($k, $all)) {
			
				$insert = array(
					"zone_id" => $z[0]['a.id'],
					"indexname" => $name,
					"colname" => $k,
				);
				
				if($is_unique)
					$insert["unique"] = $unique;

				$q = new core_db_insert("zone_index", $insert);
				$this->query($q);
				
				if($this->schema[$zone][$k] == WF_DATA) 
					$rk = "`$k`(500)";
				else
					$rk = "`$k`";
					
				if($chain_to_add_vir)
					$chain_to_add .= ", $rk";
				else {
					$chain_to_add .= " $rk";
					$chain_to_add_vir = true;
				}
			}
		}
		$chain_to_add .= ')';
		
		/* index has to be added */
		
		if($chain_to_add_vir) {
			$arr = current($sif);
			if(isset($sif[$name])) {
				$q = "ALTER TABLE `$zone` DROP INDEX `$name`";
				$this->sql_query($q);
			}
			$this->sql_query($chain_to_add);
		}
		
 		$this->a_core_cacher->store(
 			$cvar,
 			$struct
 		);
 		
		return($struct);
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
			if(!isset($all[$k])) {
				$type = $this->get_struct_type($v);
				$query = "ALTER TABLE `$name` ADD `$k` $type ;";
				$this->sql_query($query);
				$change = TRUE;
			}
		}
		
		/* vérifi les élément à enlever */
		foreach($all as $k => $v) {
			if(!isset($struct[$k])) {
				$query = "ALTER TABLE `$name` DROP `$k` ;";
				$this->sql_query($query);
				$change = TRUE;
			}
		}
		
		return $change;
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
		
		if(((int) $er[0]) != 0) {
			$er = $this->hdl->errorInfo();
			echo "* MySQL->execute()#$er[0]: $er[2]\n";
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
		$up = "";
		$where = "";
		$order = "";
		$group = "";
		$limit = "";
		$offset = "";
		
		/* fields */
		if($query_obj->type == WF_INSERT_MULTIPLE) {
			$firsttime = true;
			foreach($query_obj->arr as $fieldset) {
				$val_app = "";
				foreach($fieldset as $k => $v) {
					if($firsttime)
						$key .= empty($key) ? "`$k`" : ", `$k`";
					$val_app .= empty($val_app) ? "?" : ", ?";
					array_push($prepare_value, $this->safe_input($v));
				}
				$firsttime = false;
				$val .= empty($val) ? "($val_app)" : ", ($val_app)";
			}
		}
		elseif($query_obj->type == WF_INSERT || $query_obj->type == WF_INSERT_ID) {
			foreach($query_obj->arr as $k => $v) {
				$key .= empty($key) ? "`$k`" : ", `$k`";
				$val .= empty($val) ? "?" : ", ?";
				array_push($prepare_value, $this->safe_input($v));
			}
		}
		elseif($query_obj->type == WF_UPDATE || $query_obj->type == WF_ADV_UPDATE) {
			foreach($query_obj->arr as $k => $v) {
				$fields .= empty($fields) ? "`$k` = ?" : ", `$k` = ?";
				array_push($prepare_value, $this->safe_input($v));
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
		elseif($query_obj->type == WF_MULTIPLE_INSERT_OR_UPDATE) {
			$values_arr = array();
			
			if(is_array($query_obj->arr[0]))
				foreach($query_obj->arr[0] as $k => $v)
					$key = empty($key) ? "`$k`" : ", `$k`";
			
			if(is_array($query_obj->arr)) {
				foreach($query_obj->arr as $k => $v) {
					foreach($v as $value) {
						$separator = !is_array($values_arr) || !array_key_exists($k, $values_arr) ? "" : ",";
						
						if(isset($val[$k]))
							$values_arr[$k] .= "$separator ?";
						else
							$values_arr[$k] = "$separator ?";
						
						array_push(
							$prepare_value,
							$this->safe_input($value)
						);
					}
				}
			}
			
			foreach($values_arr as $k => $v)
				$val .= empty($val_str) ? "($v)" : ",($v)";
			
			if(is_array($query_obj->up)) {
				foreach($query_obj->up as $k => $v) {
					if(empty($up))
						$up .= "ON DUPLICATE KEY UPDATE `$v[col]` = ";
					foreach($v["op"] as $k2 => $v2)
						$up .= "$v2 ";
				}
			}
		}
		
		/* zones */
		if($query_obj->type & WF_QUERY_AS && count($query_obj->as) > 0) {
			for($a = 0; $a < count($query_obj->as); $a++) {
				$cond = "";
				if(!empty($zone)) {
					/* joins */
					if(isset($query_obj->as[$a]["J"]) && !is_null($query_obj->as[$a]["J"])) {
							$zone .= " ".$query_obj->as[$a]["J"]->build()." ";
							
							if(	($query_obj->as[$a]["J"]->join & WF_JOIN_LEFT) == WF_JOIN_LEFT ||
								($query_obj->as[$a]["J"]->join & WF_JOIN_RIGHT) == WF_JOIN_RIGHT ||
								($query_obj->as[$a]["J"]->join & WF_JOIN_STRAIGHT) == WF_JOIN_STRAIGHT
								) {
									$atom = 0;
									if(count($query_obj->as[$a]["J"]->cond_matrix) > 0)
										$cond .= "ON ";
									
									foreach($query_obj->as[$a]["J"]->cond_matrix as $k) {
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
													$k[3], 
													$prepare_value
												);
												break;
										}
									}
							}
					}
					else
						$zone .= ",";
				}
				$zone .= $query_obj->as[$a]["T"]." AS ";
				$zone .= $query_obj->as[$a]["A"]." $cond ";
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
				
				array_push(
					$prepare_value,
					$this->safe_input($v)
				);
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
							$k[4]
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
				"INSERT INTO $zone ($key) VALUES ($val);",
				$prepare_value
			);
		}
		elseif($query_obj->type == WF_INSERT_MULTIPLE) {
			$this->sql_query(
				"INSERT INTO $zone ($key) VALUES $val;",
				$prepare_value
			);
		}
		elseif($query_obj->type == WF_INSERT_ID) {
			$this->sql_query(
				"INSERT INTO $zone ($key) VALUES ($val);",
				$prepare_value
			);
			
			$id = $this->hdl->lastInsertId();
			
			/* chope toute les infos */
			$q = new core_db_select($zone);
			$where = array();
			$where[$query_obj->id] = $id;
			$q->where($where);
			$this->query($q);
			
			$query_obj->result = $q->get_result();
			$query_obj->result = $query_obj->result[0];
			
		}
		elseif($query_obj->type == WF_INDEX) {
			foreach($query_obj->indexes as $name => $s) {
 				$this->manage_index(
 					$query_obj->zone,
 					$name,
 					$s
 				);
			}
		}
		elseif($query_obj->type == WF_ADV_SELECT) {
			
			if($query_obj->req_fct & WF_REQ_FCT_COUNT) {
				if($query_obj->fields != NULL)
					$fields .= ", COUNT(*) as count_etoile";
				else
					$fields = "COUNT(*)";
			}
			elseif($query_obj->req_fct & WF_REQ_FCT_SUM)
				$fields = "SUM(*)";
			
			$select = $query_obj->req_fct & WF_REQ_FCT_DISTINCT ?
				"SELECT DISTINCT" : "SELECT";
			
			$query = "$select $fields FROM $zone $where $group $order $limit $offset";
			
			$res = $this->sql_query($query, $prepare_value);
			$query_obj->result = $this->fetch_result($res);
		}
		elseif($query_obj->type == WF_MULTIPLE_INSERT_OR_UPDATE) {
			$this->sql_query("INSERT INTO $zone ($key) VALUES $val $up", $prepare_value);
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
	
	private function get_query_var($var, $sign, $sval, &$prepare_values, $table_alias_exist = true) {
		$cond = NULL;
		$val = $this->safe_input($sval);
		$val_type = core_gettype($val);
		switch($sign) {
			case '=':
				$cond = "$var = ?";
				array_push($prepare_values, $val);
				break;
			case '==':
				if(!$table_alias_exist) {
					$cond = "$var = ?";
					array_push($prepare_values, $val);
				}
				else
					$cond = "$var = $val";
				break;
			case '~=':
				$cond = "$var LIKE ?";
				array_push($prepare_values, $val);
				break;
			case '>':
				if($val_type != WF_T_STRING || !$table_alias_exist) {
					$cond = "$var > ?";
					array_push($prepare_values, $val);
				}
				else
					$cond = "$var > $val";
				break;
			case '<':
				if($val_type != WF_T_STRING || !$table_alias_exist) {
					$cond = "$var < ?";
					array_push($prepare_values, $val);
				}
				else
					$cond = "$var > $val";
				break;
			case '>=':
				$cond = "$var >= ?";
				array_push($prepare_values, $val);
				break;
			case '<=':
				$cond = "$var <= ?";
				array_push($prepare_values, $val);
				break;
			case '!=':
				if(!$table_alias_exist) {
					$cond = "$var <> ?";
					array_push($prepare_values, $val);
				}
				else
					$cond = "$var != $val";
				break;
			case '!==':
				$cond = "$var <> ?";
				array_push($prepare_values,$val);
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
		$ret = "";
		switch($item & 0xF0) {
			case WF_VARCHAR :
				$ret .= "VARCHAR(255) NULL";break;
			case WF_SMALLINT :
				$ret .= "SMALLINT NULL";break;
			case WF_INT :
			case WF_TIME :
				$ret .= "INT NULL";break;
			case WF_BIGINT :
				$ret .= "BIGINT NULL";break;
			case WF_FLOAT :
				$ret .= "FLOAT NULL";break;
			case WF_DATA :
				$ret .= "LONGBLOB";break;
		}
		
		if($item & WF_AUTOINC)
			$ret .= " AUTO_INCREMENT";
		
		return $ret;
	}
	
	private function create_table($name, $struct) {
		$pri_list = array();
		
		$query = 'CREATE TABLE `'.$name.'` (';
		$vir = FALSE;
		foreach($struct as $k => $v) {
			if($v & WF_PRIMARY)
				$pri_list[$k] = $v;
					
			if($vir == TRUE) $query .= ",";
			$query .= '`'.$k.'` '.$this->get_struct_type($v);
			$vir = TRUE;
		}
		
		/* preparation des clés */
		if(count($pri_list) > 0) {
			$query .= ", PRIMARY KEY ( ";
			$a = 0;
			foreach($pri_list as $k => $v) {
				if($a > 0)
					$query .= ",";
				$query .= "`$k`";
				
				if(($v & 0xF0) == WF_DATA)
					$query .= "(500)";
				
				$a++;
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
		$cvar = "core_db_pdo_mysql_table_$name";
		$this->a_core_cacher->delete($cvar);
		$cvar = "core_db_pdo_mysql_zone_$name";
		$this->a_core_cacher->delete($cvar);
	}
	
	public function get_zone($name) {
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
	
	public function get_index($zoneid, $name) {
		$q = new core_db_adv_select;

		$q->alias("b", "zone_index");
		
		$q->do_comp("b.zone_id", "=", $zoneid);
		$q->do_comp("b.indexname", "=", $name);
		
		$this->query($q);
		$res = $q->get_result();
		
		return($res);
	}
	
	private function drop_index($zoneid, $name) {
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
		$cvar = "core_db_pdo_mysql_index_$zoneid/$name";
		$this->a_core_cacher->delete($cvar);
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

		$zone_index = array(
			"zone_id" => WF_INT,
			"indexname" => WF_VARCHAR,
			"colname" => WF_VARCHAR,
			"unique" => WF_VARCHAR,
		);
		$this->schema["zone_index"] = $zone_index;
		
		if(!$this->table_exists("zone")) {
			$this->create_table("zone", $zone);
			$this->create_table("zone_col", $zone_col);
		}
		
		$this->register_zone("zone_index", "Zone indexes", $zone_index);
	}
	
	private function get_table_struct($name) {
		$query = $this->sql_query("DESC $name");
		$res = $this->fetch_result($query);
		$ret = array();
		
		foreach($res as $column) {
			$ret[$column["Field"]] = $this->get_struct_type_reversed(
				$column["Type"],
				$column["Key"],
				$column["Extra"]
			);
		}
		
		return $ret;
	}
	
	private function get_struct_type_reversed($type, $key = "", $extra = "") {
		$expl = explode("(", $type);
		$ret = 0;
		
		if(stristr($type, "blob"))
			$ret |= WF_DATA;
		elseif(count($expl) > 0)
			$ret |= constant("WF_".strtoupper($expl[0]));
		
		if($key == "PRI")
			$ret |= WF_PRIMARY;
		
		if($extra == "auto_increment")
			$ret |= WF_AUTOINC;
		
		return $ret;
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
