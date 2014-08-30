<?php

class core_mail_spool_dao extends core_dao_form_db {
	
	public function add($data) {
		$data["queue"] = 0;
		$data["create_time"] = time();
		$data["send_time"] = 0;
		return parent::add($data);
	}
	
}

class core_mail_spool extends wf_agg {
	
	public function loader() {
		$this->wf->core_dao();
		
		$this->struct = array(
			"form" => array(
				"perm" => array("core:smtp"),
				"add_title" => "",
				"mod_title" => "",
			),
			"data" => array(
				"id" => array(
					"type" => WF_PRI,
					"perm" => array("core:smtp"),
				),
				"source" => array(
					"type" => WF_VARCHAR,
					"perm" => array("core:smtp"),
				),
				"recipient" => array(
					"type" => WF_VARCHAR,
					"perm" => array("core:smtp"),
				),
				"content" => array(
					"type" => WF_DATA,
					"perm" => array("core:smtp"),
				),
				"queue" => array(
					"type" => WF_VARCHAR,
					"perm" => array("core:smtp"),
				),
				"create_time" => array(
					"type" => WF_BIGINT,
					"perm" => array("core:smtp"),
				),
				"send_time" => array(
					"type" => WF_BIGINT,
					"perm" => array("core:smtp"),
				),
			),
		);
		
		$this->dao = new core_mail_spool_dao(
			$this->wf,
			"core_mail_spool",
			OWF_DAO_FORBIDDEN,
			$this->struct,
			"core_mail_spool",
			"Core mails spool"
		);
	}
	
	public function add(core_mail $mail) {
		
		$ret = array();
		
		/* sanatize recipient */
		$rcpt = $mail->get_rcpt_to();
		if(!is_array($rcpt))
			$rcpt = array($rcpt);
		
		/* add a mail spool for each of them */
		foreach($rcpt as $recipient) {
			$data = array(
				"source" => $mail->get_mail_from(),
				"recipient" => $recipient,
				"content" => $mail->render(),
			);
			$ret[] = $this->dao->add($data);
		}
		
		return $ret;
		
	}
	
}
