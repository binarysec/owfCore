<?php


class core_smtp extends wf_agg {
	public $wf;
	public $struct;
	public $dao;
	public $lang;
	
	public function loader($wf) {
		$this->wf->core_dao();
		
		$this->lang = $this->wf->core_lang()->get_context("tpl/core/smtp/list");
		
		$this->struct = array(
			"form" => array(
				"perm" => array("core:smtp"),
				"add_title" => $this->lang->ts("Add SMTP relay"),
				"mod_title" => $this->lang->ts("SMTP modification"),
			),
			"data" => array(
				"id" => array(
					"type" => WF_PRI,
					"perm" => array("core:smtp"),
				),
				"description" => array(
					"name" => $this->lang->ts("Server description"),
					"kind" => OWF_DAO_INPUT,
					"perm" => array("core:smtp"),
					"type" => WF_VARCHAR,
					"filter_cb" => array($this, "filter_description"),
				),
				"server_ip" => array(
					"size" => 22,
					"name" => $this->lang->ts("Server IP or Hostname"),
					"kind" => OWF_DAO_INPUT,
					"perm" => array("core:smtp"),
					"type" => WF_VARCHAR,
					"filter_cb" => array($this, "filter_server_ip"),
				),
				"server_port" => array(
					"size" => 10,
					"value" => 25,
					"name" => $this->lang->ts("Serveur port number"),
					"kind" => OWF_DAO_INPUT,
					"perm" => array("core:smtp"),
					"filter_cb" => array($this, "filter_server_port"),
					"type" => WF_VARCHAR
				),
				"mail_sent" => array(
					"type" => WF_INT
				),
				"error_count" => array(
					"type" => WF_INT
				),
			),
		);
		
		$this->dao = new core_dao_form_db(
			$this->wf,
			"core_smtp",
			NULL,//OWF_DAO_ADD | OWF_DAO_REMOVE, //Not used yet
			$this->struct,
			"core_smtp",
			"Core SMTP DAO"
		);
	
	}
	
	public function filter_description($item, $var) {
		if(strlen($var) < 2)
			return($this->lang->ts("Description too short"));
		return(true);
	}
	
	public function filter_server_ip($item, $var) {
		if(strlen($var) < 2)
			return($this->lang->ts("Server IP or hostname is too short"));
		$r = @gethostbyname($var);
		if($r == $var) {
			if(ip2long($var) <= 0)
				return($this->lang->ts("Invalid server IP or hostname"));
		}
		return(true);
	}
	
	
	public function filter_server_port($item, $var) {
		if($var <= 0)
			return($this->lang->ts("Port number too low"));
		if($var > 65535)
			return($this->lang->ts("Port number too high"));
		return(true);
	}
	
	public function encode_subject($subject, $encoding = 'ISO-8859-1', $transfer_encoding = 'B', $linefeed = '\r\n', $indent = 0) {
		$enc = mb_detect_encoding($subject, 'UTF-8', true);
		if($enc != 'UTF-8')
			$subject = utf8_encode($subject);
		
		return mb_encode_mimeheader(
			mb_convert_encoding($subject, $encoding, 'UTF-8'),
			$encoding,
			$transfer_encoding,
			$linefeed,
			$indent
		);
	}
	
	public function sendmail($mailfrom, $rcpt, $content, $sid=-1) {
		$queue = NULL;
		/* select best server */
		$this->select_server($sid);
		
		if(!isset($this->server["server_ip"],
			$this->server["server_port"]
		))
			return -1;
		
		/* open connection */
		$fd = fsockopen(
			$this->server["server_ip"], 
			$this->server["server_port"]
		);
		
		if(!$fd)
			return -1;

		$atom = 0;
		$log = array(date(DATE_RFC822));
		while(1) {
			$data = fread($fd, 1024);

			$log[] = trim($data);

			/* 220 */
			if($atom == 0) {
				fwrite($fd, "HELO www.owf.re\r\n");
				$atom = 1;
			}
			else if($atom == 1) {
				$buf = "MAIL FROM:<".$mailfrom.">\r\n";
				fwrite($fd, $buf);
				$atom = 2;
			}
			else if($atom == 2) {
				if(is_array($rcpt)) {
					foreach($rcpt as $val) {
						$buf = "RCPT TO:<$val>\r\n";
						fwrite($fd, $buf);
					}
				}
				else {
					$buf = "RCPT TO:<$rcpt>\r\n";
					fwrite($fd, $buf);
				}
				
				$atom = 3;
			}
			else if($atom == 3) {
				$buf = "DATA\r\n".
					$content.
					"\r\n\r\n.\r\n"
				;
				fwrite($fd, $buf);
				$atom = 4;
			}
// 			else if($atom == 4)
// 				$atom = 5;
			else {
				$e = explode(" ", $data);
				$queue = $e[count($e)-1];
				fclose($fd);
				break;
			}
		}
		
		$this->servers_id++;
		
		return(trim($queue));
	}
	
	private $servers = NULL;
	private $servers_id = 0;
	private $servers_conn = array();
	private $server = NULL;
	
	private function select_server($sid=-1) {
		if(!$this->servers) {
			if($sid != -1)
				$where = array("id" => $sid);
			else
				$where = null;
				
			$this->servers = $this->dao->get($where);
		}
		
		if($this->servers_id >= count($this->servers))
			$this->servers_id = 0;
			
		$this->server = &$this->servers[$this->servers_id];
		
	}
		
}