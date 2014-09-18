<?php

if(!defined("OWFCONSOLE"))
	die("This script should be ran using owfconsole.php");

//@todo functions check_uid, check_gid, check_perms

class core_deploy extends wf_cli_command {
	
	private $uid = "root";
	private $gid = "root";
	private $perms = "";
	private $exclude = array();
	
	public function help() {
		return $this->wf->msg("Usage: core deploy <directory> [--name] [--uid <uid>] [--gid <gid>] [--perms <perm_mask>] [--exclude <module_list,separated_with_coma>]");
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Main processing function
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function process() {
		
		if(!$this->initialize())
			return false;
		
		$this->wf->msg(" o ----- Deploying package ".$this->name." in directory ".$this->dir_main, true);
		
		/* extract files */
		$this->wf->msg(" | Extracting files", true);
		copy($this->name, $this->dir_tmp_deploy.$this->name);
		system("cd ".$this->dir_tmp_deploy." && tar -zxvf ".$this->name." >/dev/null && cd - > /dev/null", $ret);
		unlink($this->dir_tmp_deploy.$this->name);
		
		foreach($this->exclude as $mod)
			if(is_dir($this->dir_tmp_deploy.$mod))
				system("rm -r ".$this->dir_tmp_deploy.$mod);
			else
				$this->wf->msg(" + Tried to exclude module ".$mod." but this module was not in the package");
		
		/* update perms */
		$this->wf->msg(" | Updating permissions to ".$this->uid.":".$this->gid.($this->perms?" (".$this->perms.")":""), true);
		$command = "chown -R ".$this->uid.":".$this->gid." ".$this->dir_tmp_deploy;
		if($this->perms)
			$command .= " && chmod -R ".$this->perms." ".$this->dir_tmp_deploy;
		system($command);
		
		$this->wf->msg(" | Creating backups", true);
		
		$modules = array_filter(scandir($this->dir_tmp_deploy), function($f) { return $f[0] != '.'; });
		system("mkdir ".$this->dir_backup);
		foreach($modules as $mod) {
			system("cp -a ".$this->dir_main."/".$mod." ".$this->dir_backup.$mod." 2>/dev/null");
			if(!is_dir($this->dir_backup.$mod))
				return $this->wf->msg("Failed to create backup: ".$this->dir_backup.$mod);
		}
		
		$this->wf->msg(" | Stopping services", true);
		
		$this->service("cron", "stop");
		while($this->is_cron_active()) {
			$this->wf->msg(" : Waiting for cron jobs to stop");
			sleep(1);
		}
		$this->service("apache2", "stop");
		
		$this->wf->msg(" | Updating modules ".implode(", ", $modules), true);
		
		foreach($modules as $mod) {
			system("rm -r ".$this->dir_main."/".$mod);
			system("mv ".$this->dir_tmp_deploy.$mod." ".$this->dir_main."/".$mod);
		}
		
		$this->wf->msg(" | Restarting services", true);
		
		$this->service("apache2", "start");
		$this->service("cron", "start");
		
		$this->wf->msg(" o ----- Done deploying", true);
		
		return true;
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * pre process check function
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function initialize() {
		/* check root */
		exec("whoami", $ret);
		if($ret[0] != "root")
			return $this->wf->msg("/!\\ You have to run this script with root privileges /!\\", true);
		
		/* module directory */
		$directory = $this->wf->get_args(2);
		
		if(is_array($directory))
			return $this->help();
		
		$this->dir_main = ($directory[0] == '/' ? '/' : '').trim($directory, '/');
		
		if(!is_dir($this->dir_main))
			return $this->wf->msg("Directory not found: ".$this->dir_main);
		
		/* package name */
		$this->name = $this->wf->get_opt('name', true);
		if(!$this->name)
			$this->name = "owf-package.tar.gz";
		
		if(!file_exists($this->name))
			return $this->wf->msg("File not found: ".$this->name);
		
		/* options */
		$exclude = $this->wf->get_opt("exclude", true);
		if($exclude)
			$this->exclude = array_filter(explode(",", $exclude));
		
		$uid = $this->wf->get_opt("uid", true);
		if($this->check_uid($uid))
			$this->uid = $uid;
		
		$gid = $this->wf->get_opt("gid", true);
		if($this->check_gid($gid))
			$this->gid = $gid;
		
		$perms = $this->wf->get_opt("perms", true);
		if($this->check_perms($perms))
			$this->perms = $perms;
		
		/* interval vars */
		$this->dir_backup = $this->dir_main.".".date("ymd.H:i")."/";
		$this->dir_tmp_deploy = sys_get_temp_dir()."/.owf-deploy/";
		
		/* some preprocessing */
		@mkdir($this->dir_tmp_deploy);
		
		return true;
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * post process function to do some cleaning
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function post_process() {
		
		if(isset($this->dir_tmp_deploy) && is_dir($this->dir_tmp_deploy))
			system("rm -r ".$this->dir_tmp_deploy);
		
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * system functions
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	private function check_uid($uid) {
		return $uid;
	}
	
	private function check_gid($gid) {
		return $gid;
	}
	
	private function check_perms($perms) {
		return $perms;
	}
	
	private function service($sname, $action) {
		$services = array("apache2", "cron");
		$actions = array("start", "stop", "restart", "reload");
		
		if(!in_array($sname, $services))
			return $this->wf->msg("Unsupported service: $sname");
		
		if(!in_array($action, $actions))
			return $this->wf->msg("Unknown service action: $action");
		
		$command = "/usr/sbin/service $sname $action 2>&1";
		exec($command, $out, $ret);
		if($ret)
			return $this->wf->msg(" + Command failed: ".$command." (".implode(" ", $out).")", true);
	}
	
	private function is_cron_active() {
		$command = "ps -ef|grep -v grep|grep owfconsole >/dev/null";
		system($command, $status);
		return $status;
	}
	
}
