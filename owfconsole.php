#!/usr/bin/php
<?php

/* some display funcs */
function owf_msg($msg, $status = 1) { echo "$msg\n"; exit($status); }
function owf_error($msg, $status = 1) { echo "Error: $msg\n"; exit($status); }
function owf_fatal($msg, $status = 1) { echo "Fatal: $msg\n"; exit($status); }


/* search required files */
$ini = getenv("HOME")."/.owf.cli.ini";
$core_link = getenv("HOME")."/.owf.core.link";
$interactive = !($_SERVER["argc"] > 1);


/* throw errors */
if(!file_exists($ini))
	owf_fatal("Your .ini file is missing at location $ini");

if(!file_exists($core_link))
	owf_fatal("You have to create a link to your core module main.php file at $core_link");


/* load file */
require($core_link);


/* let's prepare the battle field */

class wf_cli_command {
	public function __construct($wf, $args, $opts) {
		$this->wf = $wf;
		$this->args = $args;
		$this->argc = count($args);
		$this->opts = $opts;
	}
}

class wf_console extends web_framework {
	public $interactive;
	private $scripts = array();
	private $args;
	private $argc;
	private $opts;
	
	public function __construct($ini, $db = true) {
		parent::__construct($ini, $db);
		
		/* check language */
		$l = $this->get_lang_code();
		$this->core_lang()->check_lang_route($l);
		
		/* chargement des routes */
		$this->core_route()->scan();
	}
	
	public function execute() {
		/* sanatize */
		$this->parse_line();
		
		if($this->argc > 1) {
			$module = $this->args[0];
			$script = $this->args[1];
			return $this->script($module, $script);
		}
		else {
			$this->msg("Usage <module> <script>");
			return -1;
		}
	}
	
	/* interactive mode */
	public function console() {
		$command = "";
		
		$this->msg("Welcome to OpenWF CLI");
		
		// add identification !!
		
		while(1) {
			
			$command = $this->read();
			
			$this->verbose = $this->get_opt("v") || $this->get_opt("verbose");
			
			if($this->argc < 1)
				continue;
			
			switch($this->args[0]) {
				
				/* built in commands */
				case "modules" :
				case "help" :
				case "clear" :
					$method = "cmd_".$this->args[0];
					$this->$method();
					continue 2;
				case "exit" : case "quit" : case "leave" :
					break 2;
				
				/* modules commands */
				default :
					if($this->argc > 1) {
						$module = $this->args[0];
						$script = $this->args[1];
						$this->script($module, $script);
					}
					else
						$this->msg("Usage : <module> <script> (or <module> help)");
					continue 2;
			}
			
		}
		
		$this->msg("Good bye !");
		
		return true;
	}
	
	/* execute a script */
	private function script($module = "", $script = "") {
		
		$fscript = $script;
		if(substr($fscript, strlen($fscript) - 4) != ".php")
			$fscript = "$script.php";
		
		if(isset($this->modules[$module])) {
			$path = $this->modules[$module][0]."/bin/";
			
			if(file_exists($path)) {
				
				if($this->args[1] == "show" || $this->args[1] == "help")
					$this->cmd_scripts($path, $module);
				elseif(file_exists("$path$fscript")) {
					
					unset($this->args[0], $this->args[1]);
					
					if(!isset($this->scripts["$module$fscript"])) {
						require("$path$fscript");
						$name = $module."_".$script;
						$this->scripts["$module$fscript"] = $obj = new ${"name"}($this, $this->args, $this->opts);
						
						if(get_parent_class($obj) != "wf_cli_command") {
							throw new wf_exception(
								$this,
								WF_EXC_PRIVATE,
								"Object <strong>$obj</strong> must extends wf_cli_command"
							);
						}
						
						if(!method_exists($obj, "process")) {
							throw new wf_exception(
								$this,
								WF_EXC_PRIVATE,
								"Class ".get_class($obj)." must define a process() method."
							);
						}
					}
					
					return $this->scripts["$module$fscript"]->process();
				}
				else
					$this->msg("Script $script not found. Use \"$module show\" to display available ones.");
			}
			else
				$this->msg("There are no scripts for module $module.");
		}
		elseif($this->get_last_filename("/bin/$module.php"))
			$this->msg("foo new feature !!!");
		else
			$this->msg("Module $module not found");
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * public console methods
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function msg($msg) {
		echo "* $msg\n";
	}
	
	public function clear() {
		system("clear");
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * built-in commands
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	private function cmd_modules(array $opts = array()) {
		$this->msg("Modules :");
		$modules = $this->modules;
		foreach($modules as $name => $module)
			if(file_exists($module[0]."/bin/") || $this->verbose)
				$this->msg(" $name ($module[3])");
	}
	
	private function cmd_scripts($path, $module) {
		$this->msg("Scripts of module $module :");
		$commands = scandir($path);
		foreach($commands as $k => $c)
			if($c[0] != "." && substr($c, strlen($c) - 4) == ".php")
				$this->msg(" $c");
	}
	
	private function cmd_help() {
		$this->msg("Built-in commands :");
		$this->msg(" modules (-v) : show available modules");
		$this->msg(" help : display this help");
		$this->msg(" clear : clean the window");
		$this->msg(" exit : quit owfconsole");
	}
	
	private function cmd_clear() {
		$this->clear();
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * private methods
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	
	/* read the line */
	private function read() {
		if(function_exists("readline")) {
			$line = readline("~# ");
			if(!empty($line))
				readline_add_history($line);
		}
		else {
			throw new wf_exception(
				$this,
				WF_EXC_PRIVATE,
				"You don't have function readline with your PHP installation and compatability is not done yet"
			);
			//$line = fopen(); ....
		}
		
		$this->parse_line($line);
		return $line;
	}
	
	/* parse command line */
	private function parse_line($line = "") {
		$args = array();
		$opts = array();
		
		/* sanatize */
		if(empty($line)) {
			$line = $_SERVER["argv"];
			unset($line[0]);
			$line = array_filter($line);
		}
		else
			$line = explode(" ", $line);
		
		/* parse */
		$opt = "";
		foreach($line as $arg) {
			if(!empty($arg)) {
				if(strlen($arg) > 2 && substr($arg, 0, 2) == "--")
					$opt = substr($arg, 2);
				elseif(strlen($arg) > 1 && $arg[0] == "-") {
					$opts[] = substr($arg, 1);
					$opt = "";
				}
				elseif(!empty($opt)) {
					$opts[$opt] = $arg;
					$opt = "";
				}
				else {
					$args[] = $arg;
					$opt = "";
				}
			}
		}
		
		$this->command = $line;
		$this->args = $args;
		$this->argc = count($args);
		$this->opts = $opts;
		$this->opts = $opts;
	}
	
	private function get_opt($name, $long = false) {
		return $long && isset($this->opts[$name]) ?
			$this->opts[$name] : in_array($name, $this->opts) || isset($this->opts[$name]);
	}
	
}

/* and now let's rock */

try {
	$wfc = new wf_console($ini);
	$wfc->interactive = $interactive;
	if($interactive)
		$wfc->console();
	else
		$wfc->execute();
}
catch (wf_exception $e) {
	$msg = "/!\\ Exception:\n";
	$i = 0;
	if(is_array($e->messages))
		foreach($e->messages as $v)
			$msg .= "* (".$i++.") $v\n";
	else
		$msg .= $e->messages."\n";
	owf_msg($msg, 2);
}
