<?php

class core_utils extends wf_agg {
	public $wf; 
	
	private $session;
	private $waf_site;
	private $lang;
	private $pref_session;

	private $a_site_zone;
	
	public function loader($wf) {
		$this->wf = $wf;
		$this->session = $this->wf->session();

		$this->lang = $this->wf->core_lang()->get_context(
			"core/utils"
		);

	}


	public function check_valid_ip_address($ip) {
		return ip2long($ip) != 0;

// 		$this->pref_session = $this->wf->core_pref()->register_group(
// 			"core_utils"
// 		);
// 		$ip_rules = $this->pref_session->register(
// 			"ip rules",
// 			"List of invalid IP Address for proxy",
// 			CORE_PREF_VARCHAR,
// 			"127.0.0.1/8,10.0.0.0/8,192.168.0.0/16,224.0.0.0/4,240.0.0.0/4,0.0.0.0/32,172.16.0.0/12"
// 		);
// 		
// 		$ret = $this->check_ip(NULL,$ip);
// 		if(!is_bool($ret) || $ret != TRUE)
// 			return FALSE;
// 			
// 		$rules = explode(",",$ip_rules);
// 		$ip_ar = explode(".",$ip);
// 		if(count($ip_ar) != 4) {
// 			return false;
// 		}
// 		
// 		foreach($rules as $k => $v) {
// 			$ar = explode("/",$v);
// 			$first = $ar[0];
// 			$mask = $ar[1];
// 			if($mask <= 0) return false;
// 			$ip_binary_string = sprintf("%032b",ip2long($ip));
// 			$first_binary_string = sprintf("%032b",ip2long($first));
// 			if(substr_compare($ip_binary_string,$first_binary_string,0,$mask) == 0)
// 				return false; 
// 		}
// 		return true;	
	}
	

	
	public function generate_ref($name,$table_name,$key_field = NULL) {
		if(!$key_field) $key_field = "ref";
		
		$id_start = 3;
		$rand = "";
		while(1) {
			/* nombre aléatoire */
			
			$r = $this->wf->get_rand(10);
			for($a = 0; $a < strlen($r); $a++) 
				$rand .= ord($r[$a]);
			
			while(1) {
				$p1 = strtoupper($name[rand(0, strlen($name)-1)]);
				$p2 = strtoupper($name[rand(0, strlen($name)-1)]);
				
				$key = $p1.$p2.
					substr($rand, 0, $id_start);
				
				if(preg_match("/^([A-Z0-9]+)$/", $key) == TRUE)
					break;
			}
			
			/* check si la ref existe */
			$q = new core_db_select($table_name);
			$where = array(
				$key_field => $key
			);
	
			$q->where($where);
			$this->wf->db->query($q);
			$res = $q->get_result();
			
			/* si on a pas de result alors c'est bon */
			if(count($res) == 0)
				break;
			
			/* augmente la taille */
			$id_start++;
		}
		return($key);
	}
	
	public function generate_password(
		$size_min = 8,
		$size_max = 11
	) {
		$size = rand($size_min, $size_max);
		$return = NULL;
		for($a = 0; $a <= $size; $a++) {
			$bet = rand(0x21, 0x7d);
			if(
				($bet >= 0x30 && $bet <= 0x39) ||
				($bet >= 0x41 && $bet <= 0x5a) ||
				($bet >= 0x61 && $bet <= 0x7a)
				)
				$return .= chr($bet);
			else 
				$a--;
		}
		return($return);
	}
	
	public function get_user_agent_information($user_agent) {
		$info = array();
		/* Browser */
		if($pos = strpos($user_agent,"Firefox"))  {
			$t = explode("/",substr($user_agent,$pos));
			$info["browser"] = "Firefox";
			$info["browser_version"] = $t[1];
			$info["browser_code"] = "firefox";
		}else if ($pos = strpos($user_agent,"MSIE")) {
			$t = explode(" ",substr($user_agent,$pos));
			$info["browser"] = "Internet Explorer";
			$info["browser_version"] = $t[1];
			$info["browser_code"] = "msie";
		}else if ($pos = strpos($user_agent,"Chrome")) {
			$t = explode("/",substr($user_agent,$pos));
			$info["browser"] = "Chrome";
			$info["browser_version"] = $t[1];
			$info["browser_code"] = "chrome";
		}else if ($pos = strpos($user_agent,"Safari")) {
			$t = explode("/",substr($user_agent,$pos));
			$info["browser"] = "Safari";
			$info["browser_version"] = $t[1];
			$info["browser_code"] = "safari";
		}else if ($pos = strpos($user_agent,"Opera")) {
			$t = explode("/",substr($user_agent,$pos));
			$info["browser"] = "Opera";
			$info["browser_version"] = $t[1];
			$info["browser_code"] = "opera";
		}
		
		/* OS */
		if($pos = strpos($user_agent,"Ubuntu") ) {
			$t = explode("/",substr($user_agent,$pos));
			$info["os"] = "Ubuntu";
			$info["os_version"] = $t[1];
			$info["os_code"] = "ubuntu";
		}else if ($pos = strpos($user_agent,"Windows NT 5.1")) {
			$info["os"] = "Windows XP";
			$info["os_code"] = "windows";
		}else if ($pos = strpos($user_agent,"Windows NT 6.0")) {
			$info["os"] = "Windows Vista";
			$info["os_code"] = "windows";
		}else if ($pos = strpos($user_agent,"Intel Mac OS X")) {
			$t = explode(" ",substr($user_agent,$pos));
			$info["os"] = "OS X";
			$info["os_code"] = "mac";
		}else if ($pos = strpos($user_agent,"Linux")) {
			$t = explode(" ",substr($user_agent,$pos));
			$info["os"] = "Linux";
			$info["os_code"] = "linux";
		}	
		return $info;
	}
	
	
	function email_valid($temp_email) {
        function valid_dot_pos($email) {
            $str_len = strlen($email);
            for($i=0; $i<$str_len; $i++) {
                $current_element = $email[$i];
                if($current_element == "." && ($email[$i+1] == ".")) {
                    return false;
                    break;
                }
                else {

                }
            }
            return true;
        }
        
        function valid_local_part($local_part) {
            if(preg_match("/[^a-zA-Z0-9-_@.!#$%&'*\/+=?^`{\|}~]/", $local_part)) {
                return false;
            }
            else {
                return true;
            }
        }
        
        function valid_domain_part($domain_part) {
            if(preg_match("/[^a-zA-Z0-9@#\[\].]/", $domain_part)) {
                return false;
            }
            elseif(preg_match("/[@]/", $domain_part) && preg_match("/[#]/", $domain_part)) {
                return false;
            }
            elseif(preg_match("/[\[]/", $domain_part) || preg_match("/[\]]/", $domain_part)) {
                $dot_pos = strrpos($domain_part, ".");
                if(($dot_pos < strrpos($domain_part, "]")) || (strrpos($domain_part, "]") < strrpos($domain_part, "["))) {
                    return true;
                }
                elseif(preg_match("/[^0-9.]/", $domain_part)) {
                    return false;
                }
                else {
                    return false;
                }
            }
            else {
                return true;
            }
        }
        
        // trim() the entered E-Mail
        $str_trimmed = trim($temp_email);
        // find the @ position
        $at_pos = strrpos($str_trimmed, "@");
        // find the . position
        $dot_pos = strrpos($str_trimmed, ".");
        // this will cut the local part and return it in $local_part
        $local_part = substr($str_trimmed, 0, $at_pos);
        // this will cut the domain part and return it in $domain_part
        $domain_part = substr($str_trimmed, $at_pos);
        if(!isset($str_trimmed) || is_null($str_trimmed) || empty($str_trimmed) || $str_trimmed == "") {
            return false;
        }
        elseif(!valid_local_part($local_part)) {
            return false;
        }
        elseif(!valid_domain_part($domain_part)) {
            return false;
        }
        elseif($at_pos > $dot_pos) {
            return false;
        }
        elseif(!valid_local_part($local_part)) {
            return false;
        }
        elseif(($str_trimmed[$at_pos + 1]) == ".") {
            return false;
        }
        elseif(!preg_match("/[(@)]/", $str_trimmed) || !preg_match("/[(.)]/", $str_trimmed)) {
            return false;
        }
        else {
            return true;
        }
	} 
	
	/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * general checks 
	 ** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	
	public function check_length($item, $var) {
		return strlen($var) > 1;
	}
	
	public function check_port($item, $port) {
		if(!is_numeric($port))
			return $this->lang->ts("The port must be a numeric value");
		if($port <= 0)
			return $this->lang->ts("Port number is too low");
		if($port > 65535)
			return $this->lang->ts("Port number is too high");
		
		return true;
	}
	
	public function check_ip($item, $var) {
		$t = explode(".",$var);
		
		if(count($t) != 4)
			return($this->lang->ts("Incorrect IPv4 syntax"));
		
		foreach($t as $k => $v)
			if($v < 0 || $v > 255)
				return($this->lang->ts("Invalid IP address"));
		
		if($var == "0.0.0.0" || $var == "255.255.255.255")
			return($this->lang->ts("Unauthorized IP address"));
		
		$long = ip2long(trim($var));
		if(!isset($long) || $long == -1 || $long === FALSE)
			return($this->lang->ts("Invalid IP address"));
			
		return(true);
	}
	
	public function read_ip($item, $ip) {
		return $ip ? long2ip($ip) : "";
	}

	public function convert_ip($item, $ip) {
		return ip2long(trim($ip));
	}
	
	public function encode_base64($item, $var) {
		return $var ? base64_encode($var) : "";
	}
	
	public function decode_base64($item, $var) {
		return base64_decode($var);
	}
	
	public function is_ipv4_public($ip) {
		$elements = explode(".", $ip);
		
		if(	count($elements) < 4 ||
			(int) $elements[0] == 10 ||
			((int) $elements[0] == 172 && (int) $elements[1] > 15 && (int) $elements[1] < 32) ||
			((int) $elements[0] == 192 && (int) $elements[1] == 168)
			)
			return false;
		
		return true;
	}
	
	/* deprecated, delete when possible */
	public function check_name($item, $var) {
		return $this->check_length($item, $var) ? true :
			$this->lang->ts("Name is too short");
	}
	public function check_description($item, $var) {
		return $this->check_length($item, $var) ? true :
			$this->lang->ts("Description too short");
	}
	
	public function whois_emails($domain) {
		
		if(empty($domain))
			return false;
		
		exec("whois $domain 2>/dev/null", $out, $ret);
		
		if($ret == 127) {
			error_log("core_utils error: whois package is not installed on the server");
			return false;
		}
		
		preg_match_all("/[^ ]*@[^ ]*/i", implode(" ", $out), $matches);
		$ret = current($matches);
		$ret[] = "postmaster@$domain";
		
		$ret = array_unique($ret);
		sort($ret);
		return $ret;
	}
	
	public function dig($domain, $type = "") {
		if(empty($domain))
			return false;
		
		exec("dig $domain 2>/dev/null", $out, $ret);
		
		if($ret == 127) {
			error_log("core_utils error: dig package is not installed on the server");
			return false;
		}
		
		preg_match_all("/\n$domain.*\n/", implode("\n", $out), $matches);
		$ret = current($matches);
		
		$ret = array_unique($ret);
		sort($ret);
		
		if(!empty($type)) {
			foreach($ret as $k => $line) {
				$line = str_replace("\t\t", "\t", $line);
				$line = str_replace("\n", "", $line);
				$elements = explode("\t", $line);
				if(count($elements) == 5) {
					if($elements[3] == $type)
						return $elements;
				}
				else
					unset($ret[$k]);
			}
		}
		return empty($ret) ? false : $ret;
	}
	
}
