<?php

if(!defined("OWFCONSOLE"))
	die("This script should be ran using owfconsole.php");

class core_mail_spooler extends wf_cli_command {
	
	public function help() {
		$this->wf->msg("Usage: core mail_spooler [--count <x>] [--timeout <x>] [-r] [--retry] [-v] [--verbose]");
		$this->wf->msg("Sample: core mail_spooler --count 50 -r -v");
	}
	
	public function process() {
		
		/* aggs */
		$core_smtp = $this->wf->core_smtp();
		$core_mail_spool = $this->wf->core_mail_spool();
		$core_lock = $this->wf->core_lock();
		
		/* try to get the lock file */
		$lock = $core_lock->try_lock("core", "mail_spooler", "mails");
		if(!$lock) {
			$this->wf->msg("Fatal: script lock. Another instance is already running ?");
			return false;
		}
		
		/* options */
		$verbose = $this->wf->get_opt("v") || $this->wf->get_opt("verbose");
		$retry = $this->wf->get_opt("r") || $this->wf->get_opt("retry");
		$count = intval($this->wf->get_opt("count", true));
		$timeout = intval($this->wf->get_opt("timeout", true));
		if(!$count)
			$count = $core_mail_spool->dao->count(array(array("queue", ($retry ? "<=" : "="), 0)));
		
		if($count) {
			if($verbose)
				$this->wf->msg("Sending a total of $count mails.");
			
			if($timeout) {
				$old_timeout = ini_get("default_socket_timeout");
				ini_set("default_socket_timeout", $timeout);
			}
			
			/* send mails */
			for($i = 0, $maxloop = min(10, $count); $i < $maxloop; $i += $count) {
				$q = new core_db_adv_select("core_mail_spool");
				$q->do_comp("send_time", $retry ? "<=" : "=", 0);
				$q->limit($count, $i * $count);
				$this->wf->db->query($q);
				$tosend = $q->get_result();
				
				if($verbose)
					$this->wf->msg("Processing $maxloop from $count mails.");
				
				foreach($tosend as $mail) {
					
					if($verbose)
						$this->wf->msg("Sending mail from $mail[source] to $mail[recipient].");
					
					/* send */
					$queue = $core_smtp->sendmail($mail["source"], $mail["recipient"], $mail["content"]);
					$sent = is_string($queue) && $queue > 0;
					
					/* update database */
					$core_mail_spool->dao->modify(
						array("id" => $mail["id"]),
						array("queue" => $queue, "send_time" => $sent ? time() : -1)
					);
				}
			}
			
			if($timeout)
				ini_set("default_socket_timeout", $old_timeout);
			
			if($verbose)
				$this->wf->msg("Done sending mails.");
		}
		else {
			if($verbose)
				$this->wf->msg("No mails to send.");
		}
		
		$core_lock->unlock("core", "mail_spooler");
		
		return true;
	}
	
}
