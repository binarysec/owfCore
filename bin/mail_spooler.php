<?php

if(!defined("OWFCONSOLE"))
	die("This script should be ran using owfconsole.php");

class core_mail_spooler extends wf_cli_command {
	
	public function help() {
		$this->wf->msg("core mail_spooler [--count <x>] [--timeout <x>] [-r --retry] [-v --verbose] [--block <x>] [--clean <x>] [--fclean <x>] [-h --help]");
		$this->wf->msg("");
		$this->wf->msg("--count <x>    : Send only the first x mails");
		$this->wf->msg("--timeout <x>  : Wait x seconds trying to connect to the mail server");
		$this->wf->msg("-r / --retry   : Retry mails failed to be sent");
		$this->wf->msg("-v / --verbose : Output status messages");
		$this->wf->msg("--block <x>    : Block retry for mails failed to be sent for x seconds");
		$this->wf->msg("--clean <x>    : Remove sent mails older than x seconds from database");
		$this->wf->msg("--fclean <x>   : Remove unsent mails older than x seconds from database");
		$this->wf->msg("-h / --help    : Show this message");
	}
	
	public function verb($msg, $raw = false) {
		if($this->verbose)
			return $this->wf->msg($msg, $raw);
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
		
		if($this->wf->get_opt("h") || $this->wf->get_opt("help"))
			return $this->help();
		
		/* options */
		$this->verbose = $verbose = $this->wf->get_opt("v") || $this->wf->get_opt("verbose");
		$retry = $this->wf->get_opt("r") || $this->wf->get_opt("retry");
		$count = intval($this->wf->get_opt("count", true));
		$timeout = $this->wf->get_opt("timeout", true);
		$block = $this->wf->get_opt("block", true);
		$this->clean = $this->wf->get_opt("clean", true);
		$this->fclean = $this->wf->get_opt("fclean", true);
		if(!$count)
			$count = $core_mail_spool->dao->count(array(array("send_time", ($retry ? "<=" : "="), 0)));
		
		/* sanatize */
		$count = is_numeric($count) ? intval($count) : false;
		$timeout = is_numeric($timeout) ? intval($timeout) : false;
		$block = is_numeric($block) ? intval($block) : false;
		$this->clean = is_numeric($this->clean) ? intval($this->clean) : false;
		$this->fclean = is_numeric($this->fclean) ? intval($this->fclean) : false;
		
		if($count) {
			$this->verb("Sending a total of $count mails.");
			
			if($timeout) {
				$old_timeout = ini_get("default_socket_timeout");
				ini_set("default_socket_timeout", $timeout);
			}
			
			/* send mails */
			$done = 0;
			$max_per_loop = min(10, $count);
			do {
				$q = new core_db_adv_select("core_mail_spool");
				$q->do_comp("send_time", $retry ? "<=" : "=", 0);
				$q->limit($max_per_loop);
				$this->wf->db->query($q);
				$tosend = $q->get_result();
				
				$total = count($tosend);
				$this->verb("Processing $done to ".($done + $total)." from $count mails.");
				$done += $total;
				
				foreach($tosend as $mail) {
					
					if(strlen($mail["queue"]) > 0) {
						$this->verb("Mail from $mail[source] to $mail[recipient] was already sent, fixing.");
						$core_mail_spool->dao->modify(
							array("id" => $mail["id"]),
							array("send_time" => $mail["create_time"])
						);
						continue;
					}
					
					$this->verb("Sending mail from $mail[source] to $mail[recipient].");
					
					/* send */
					$queue = $core_smtp->sendmail($mail["source"], $mail["recipient"], $mail["content"]);
					$sent = is_string($queue) && strlen($queue) > 0;
					
					/* update database */
					if(!$sent && $block && $mail["create_time"] < (time() - $block)) {
						$core_mail_spool->dao->modify(
							array("id" => $mail["id"]),
							array("queue" => "FAILED", "send_time" => time())
						);
					}
					else
						$core_mail_spool->dao->modify(
							array("id" => $mail["id"]),
							array("queue" => $queue, "send_time" => $sent ? time() : -1)
						);
				}
			} while($total && $total == $max_per_loop);
			
			if($timeout)
				ini_set("default_socket_timeout", $old_timeout);
			
			$this->verb("Done sending mails.");
		}
		else {
			$this->verb("No mails to send.");
		}
		
		$this->clean();
		
		$core_lock->unlock("core", "mail_spooler");
		
		return true;
	}
	
	private function clean() {
		if(!$this->clean && !$this->fclean)
			return false;
		
		$q = new core_db_adv_delete("core_mail_spool");
		if($this->clean) {
			$q->do_open();
			$q->do_comp("create_time", "<", time() - $this->clean);
			$q->do_comp("queue", "!=", 0);
			$q->do_comp("queue", "!=", -1);
			$q->do_comp("queue", "!=", "FAILED");
			$q->do_close();
		}
		if($this->fclean) {
			if($this->clean)
				$q->do_or();
			$q->do_open();
			$q->do_comp("create_time", "<", time() - $this->fclean);
			$q->do_open();
				$q->do_comp("queue", "=", 0);
				$q->do_or();
				$q->do_comp("queue", "=", -1);
				$q->do_or();
				$q->do_comp("queue", "=", "FAILED");
			$q->do_close();
			$q->do_close();
		}
		$ret = $this->wf->db->query($q);
		
		return $ret;
	}
	
}
