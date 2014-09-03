<?php

if(!defined("OWFCONSOLE"))
	die("This script should be ran using owfconsole.php");

define("CORE_PACKER_DIRECTORY_BRANCHES", "branches");
define("CORE_PACKER_DIRECTORY_STABLE", "stable");

class core_packer extends wf_cli_command {
	
	public function help() {
		//$this->wf->msg("Usage: core packer --modules <core,admin,session,...> [--server] [-b] [-s]");
		$this->wf->msg("Usage: core packer --modules <core,admin,session,...> [-b] [--name] [-r] [-v]", true);
		$this->wf->msg("  --modules      : list of modules separated with a coma", true);
		$this->wf->msg("  --name         : change the default package name owf-package.tar.gz", true);
		$this->wf->msg("  -b             : pick directory '".CORE_PACKER_DIRECTORY_BRANCHES."' instead of '".CORE_PACKER_DIRECTORY_STABLE."' (svn only)", true);
		$this->wf->msg("  --remote / -r  : use git branch 'master' instead of the working directory (git only)", true);
		$this->wf->msg("  --verbose / -v : turn on verbosity", true);
		//$this->wf->msg("-server : automatically send the package to this location");
		//$this->wf->msg("-s : send only to server, does not build the package and assume it's here");
		return false;
	}
	
	public function verb($msg, $raw = false) {
		if($this->verbose)
			$this->wf->msg($msg, $raw);
	}
	
	public function process() {
		
		/* get options */
		$server = $this->wf->get_opt('server', true);
		$this->name = $this->wf->get_opt('name', true);
		$this->remote = $this->wf->get_opt('remote', true) || $this->wf->get_opt('r');
		$this->branches = $this->wf->get_opt('b');
		$this->verbose = $this->wf->get_opt('verbose', true) || $this->wf->get_opt('v');
		$this->modules = explode(",", $this->wf->get_opt('modules', true));
		$this->modules = array_filter($this->modules);
		
		/* sanatize */
		if(empty($this->modules))
			return $this->help();
		
		$missing = array();
		foreach($this->modules as $module) {
			if(!isset($this->wf->modules[$module])) {
				$missing[] = $module;
			}
		}
		if(!empty($missing))
			return $this->wf->msg("Fatal: modules ".implode(" ", $missing)." were not found");
		
		if(!$this->name)
			$this->name = "owf-package.tar.gz";
		//$sendonly = $this->wf->get_opt('s');
		
		//if(!$sendonly)
			$this->build();
		
		/* send to server */
		//if($server && file_exists($this->name))
			//system("scp $this->name $server");
			//copy($this->name, $server);
		
		return true;
	}
	
	private function build() {
		$this->wf->msg(" o ----- Building package ".$this->name, true);
		$fname = sys_get_temp_dir()."/owf-packer/";
		$dir_git_tmp = sys_get_temp_dir()."/owf-packer-git/".$this->wf->generate_password(5, true)."/";
		if(is_dir($dir_git_tmp))
			system("rm -r $dir_git_tmp/*");
		
		$this->wf->msg(" | Building directory structure", true);
		$modules_full = array();
		foreach($this->modules as $module) {
			
			/* build module wanted path */
			$path = $this->wf->modules[$module][0];
			$is_git = is_dir($path."/.git");
			$newpath = "";
			$exist = true;
			$rail = explode('/', $path);
			$atom = 0;
			
			foreach($rail as $k => $dir) {
				$add = $dir;
				
				/* for svn directory structure, use stable or branches */
				if($dir == "branches" || $dir == "stable") {
					$add = $this->branches ? CORE_PACKER_DIRECTORY_BRANCHES : CORE_PACKER_DIRECTORY_STABLE;
					$atom = 2;
				}
				
				/* retrieve the last version only */
				elseif($atom == 2) {
					if(!file_exists($newpath)) {
						$exist = false;
						break;
					}
					$versions = array();
					$lookup = scandir($newpath);
					foreach($lookup as $v)
						$versions[$v] = intval(str_replace("_", "", $v));
					arsort($versions);
					$add = key($versions);
				}
				
				/* add rail element */
				$newpath .= "$add/";
			}
			
			/* git remote master */
			if($is_git && $this->remote) {
				$out = $matches = null;
				exec("cd $newpath && git remote -v && cd -", $out);
				preg_match("/\s.*\s/", current($out), $matches);
				$remote = trim(current($matches));
				$expl = explode("/", $remote);
				$last = array_pop($expl);
				$expl = explode(".", $last);
				$repository = current($expl);
				
				if(!is_dir($dir_git_tmp))
					@mkdir($dir_git_tmp, 0777, true);
				
				passthru("cd $dir_git_tmp && git clone $remote -q && cd - >/dev/null", $ret);
				if($ret)
					return $this->wf->msg("Fatal: error cloning $remote into $dir_git_tmp");
				
				$submodules = $dir_git_tmp."/".$repository;
				passthru("cd $submodules && git submodule init -q && git submodule update -q && cd - >/dev/null", $ret);
				if($ret)
					return $this->wf->msg("Fatal: error fetching submodules in $submodules");
				$newpath = $dir_git_tmp.$repository;
			}
			
			if(!$exist) {
				$this->wf->msg("Fatal: path $newpath not found");
				return false;
			}
			
			$this->verb(" |  [$module] -> $newpath", true);
			$modules_full[$module] = $newpath;
		}
		
		$this->wf->msg(" | Copying modules to temporary directory", true);
		@mkdir($fname, 0777, true);
		foreach($modules_full as $name => $path)
			system("cp -a $path $fname/".$name);
		
		$this->wf->msg(" | Removing .svn and .git directories", true);
		system('find '.$fname.' -name "\.svn" -print0 | xargs -0 rm -r 2>/dev/null');
		system('find '.$fname.' -name "\.git" -print0 | xargs -0 rm -r 2>/dev/null');
		
		$this->wf->msg(" | Building archive", true);
		$pwd = getcwd();
		chdir($fname);
		system("tar zcvf ".$this->name." * >/dev/null");
		chdir($pwd);
		if(file_exists($this->name))
			unlink($this->name);
		system("mv $fname/".$this->name." .");
		
		$this->wf->msg(" | Cleaning temporary directory", true);
		system("rm -r $fname/*");
		rmdir($fname);
		
		if(is_dir($dir_git_tmp))
			$this->wf->msg(" | Please remove directory $dir_git_tmp by yourself, i do not have enough rights", true);
		
		$this->wf->msg(" o ----- Done packaging", true);
	}
	
}
