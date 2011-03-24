<?php


class core_smtp extends wf_agg {
	public $wf;
	public $struct;
	public $dao;
	public $lang;
	
	public function loader($wf) {
		$this->wf = $wf;
		$this->wf->core_dao();
		
		$this->lang = $this->wf->core_lang()->get_context("tpl/core/smtp/list");
		
		$this->struct = array(
			"form" => array(
				"perm" => array("core:smtp"),
				
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
					"filter_cb" => array($this, "check_description"),
					"type" => WF_VARCHAR
				),
				"server_ip" => array(
					"size" => 22,
					"name" => $this->lang->ts("Server IP or Hostname"),
					"kind" => OWF_DAO_INPUT,
					"perm" => array("core:smtp"),
					
					"type" => WF_VARCHAR
				),
				"server_port" => array(
					"size" => 10,
					"value" => 25,
					"name" => $this->lang->ts("Serveur port number"),
					"kind" => OWF_DAO_INPUT,
					"perm" => array("core:smtp"),
					"filter_cb" => array($this, "check_port"),
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
			OWF_DAO_ADD | OWF_DAO_REMOVE,
			$this->struct,
			"core_smtp",
			"Core SMTP DAO"
		);
	
	}
	
	public function check_description($item, $var) {
		if(strlen($var) < 2)
			return($this->lang->ts("Description too short"));

		return(true);
	}
	
	public function check_port($item, $var) {
		if($var <= 0)
			return($this->lang->ts("Port number too low"));
		if($var >= 65535)
			return($this->lang->ts("Port number too high"));
			
		return(true);
	}
	
	public function sendmail($mailfrom, $rcpt, $content, $sid=-1) {
		/* select best server */
		$this->select_server($sid);


		/* open connection */
		$fd = fsockopen(
			$this->server["server_ip"], 
			$this->server["server_port"]
		);

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
				fclose($fd);
				break;
			}
		}
		
		$this->servers_id++;
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
