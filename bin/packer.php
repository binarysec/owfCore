<?php

if(!defined("OWFCONSOLE"))
	die("This script should be ran using owfconsole.php");

define("CORE_PACKER_DIRECTORY_BRANCHES", "branches");
define("CORE_PACKER_DIRECTORY_STABLE", "stable");

class core_packer extends wf_cli_command {
	
	public function help() {
		//$this->wf->msg("Usage: core packer --modules <core,admin,session,...> [--server] [-b] [-s]");
		$this->wf->msg("Usage: core packer --modules <core,admin,session,...> [-b] [--name]");
		//$this->wf->msg("-server : automatically send the package to this location");
		$this->wf->msg("-b : pick directory '".CORE_PACKER_DIRECTORY_BRANCHES."' instead of '".CORE_PACKER_DIRECTORY_STABLE."'");
		$this->wf->msg("--name : change the default package name owf-package.tar.gz");
		//$this->wf->msg("-s : send only to server, does not build the package and assume it's here");
	}
	
	public function process() {
		
		/* get options */
		$server = $this->wf->get_opt('server', true);
		
		$name = $this->wf->get_opt('name', true);
		if(!$name)
			$name = "owf-package.tar.gz";
		$this->name = $name;
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
		
		/* get options */
		$branches = $this->wf->get_opt('b');
		$modules = explode(",", $this->wf->get_opt('modules', true));
		$modules = array_filter($modules);
		
		/* sanatize */
		if(empty($modules)) {
			$this->help();
			return false;
		}
		
		/* lookup modules path */
		$modules_full = array();
		foreach($modules as $module) {
			
			/* check module in ini file */
			if(!isset($this->wf->modules[$module])) {
				$this->wf->msg("Fatal: module $module not found");
				return false;
			}
			
			/* build module wanted path */
			$path = $this->wf->modules[$module][0];
			$newpath = "";
			$exist = true;
			$rail = explode('/', $path);
			$atom = 0;
			foreach($rail as $k => $dir) {
				$add = $dir;
				
				/* for svn directory structure, use stable or branches */
				if($dir == "branches" || $dir == "stable") {
					$add = $branches ? CORE_PACKER_DIRECTORY_BRANCHES : CORE_PACKER_DIRECTORY_STABLE;
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
			
			if(!$exist) {
				$this->wf->msg("Fatal: path $newpath not found");
				return false;
			}
			
			$modules_full[$module] = $newpath;
		}
		
		/* create temporary directory */
		$fname = '/tmp/owf-packer/';
		@mkdir($fname, 0777, true);
		
		/* copy modules */
		foreach($modules_full as $name => $path)
			system("cp -a $path $fname/".$name);
		
		/* remove .svn files */
		system('find '.$fname.' -name "\.svn" -print0 | xargs -0 rm -r 2>/dev/null');
		system('find '.$fname.' -name "\.git" -print0 | xargs -0 rm -r 2>/dev/null');
		
		/* build archive */
		$pwd = getcwd();
		chdir($fname);
		system("tar zcvf ".$this->name." *");
		chdir($pwd);
		if(file_exists($this->name))
			unlink($this->name);
		system("mv $fname/".$this->name." .");
		
		/* remove temporary directory */
		system("rm -r $fname/*");
		rmdir($fname);
	}
	
}
