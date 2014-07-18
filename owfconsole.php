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
define("OWFCONSOLE", "okay");

abstract class wf_cli_command {
	
	public function __construct($wf, $args, $opts) {
		$this->wf = $wf;
		$this->args = $args;
		$this->argc = count($args);
		$this->opts = $opts;
	}
	
	public abstract function process();
	
}

class wf_console extends web_framework {
	public $interactive;
	public $verbose;
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
		
		$this->verbose = $this->get_opt("v") || $this->get_opt("verbose", true);
		
		$module = $this->args[0];
		$script = isset($this->args[1]) ? $this->args[1] : $this->args[0];
		return $this->script($module, $script);
	}
	
	/* interactive mode */
	public function console() {
		$command = "";
		
		$this->msg("Welcome to OpenWF CLI");
		
		// add identification !!
		
		while(1) {
			
			$command = $this->read();
			
			$this->verbose = $this->get_opt("v") || $this->get_opt("verbose", true);
			
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
					$module = $this->args[0];
					$script = isset($this->args[1]) ? $this->args[1] : $this->args[0];
					$this->script($module, $script);
					continue 2;
			}
			
		}
		
		$this->msg("Good bye !");
		
		return true;
	}
	
	/* execute a script */
	private function script($module = "", $script = "") {
		
		$lscript = $this->locate_file(
			substr($module, strlen($module) - 4) != ".php" ?
			"/bin/$module.php" : "/bin/$module",
			true
		);
		
		if(isset($this->modules[$module])) {
			if($this->argc > 1) {
				$path = $this->modules[$module][0]."/bin/";
				
				if(file_exists($path)) {
					
					/* append script to path */
					$path .= substr($script, strlen($script) - 4) != ".php" ?
						"$script.php" : $script;
					
					if($this->args[1] == "show" || $this->args[1] == "help")
						$this->cmd_scripts($this->modules[$module][0]."/bin/", $module);
					elseif(file_exists("$path")) {
						unset($this->args[0], $this->args[1]);
						
						$obj = $this->getscript($module, $script, "$path");
						$obj->args = array_values($this->args);
						$obj->opts = $this->opts;
						return $this->run_command($obj);
					}
					else
						$this->msg("Script $script not found. Use \"$module show\" to display available ones.");
				}
				else
					$this->msg("There are no scripts for module $module.");
			}
			else
				$this->msg("Usage : <module> <script> (or <module> help)");
		}
		elseif($lscript) {
			unset($this->args[0]);
			$obj = $this->getscript(end($lscript), $module, reset($lscript));
			$obj->args = array_values($this->args);
			$obj->opts = $this->opts;
			return $this->run_command($obj);
		}
		else {
			switch($this->args[0]) {
				/* built in commands */
				case "modules" :
				case "help" :
				case "clear" :
					$method = "cmd_".$this->args[0];
					return $this->$method();
				case "exit" : case "quit" : case "leave" :
					return $this->msg("<(^o^<) (>^o^<) (>^o^)> Let's dance !");
				default:
					$this->msg("Module or script $module not found");
			}
		}
		
	}
	
	private function run_command(wf_cli_command &$obj) {
		
		$session = $this->session();
		
		if(method_exists($obj, "perms")) {
			if(!$session)
				$this->msg("This command require authentication but the session module is not installed.");
			
			$this->msg("This command require authentication. Please login.");
			
			/* sanatize perms */
			$require = $obj->perms();
			if(!is_array($require))
				$require = array($require);
			
			/* identify */
			$u = $this->get_opt("owfUsername", true);
			$p = $this->get_opt("owfPassword", true);
			
			if(!$u)
				$u = $this->ask("Username : ");
			if(!$p)
				$p = $this->ask("Password : ");
			
			if(!$session->identify($u, $p))
				return $this->msg("Authentication failed. Wrong credentials.");
			
			$session->check_session();
			
			if(!$session->check_permission($require))
				return $this->msg("You don't have enough privileges to run this command.");
		}
		
		return $obj->process();
	}
	
	private function getscript($module, $script, $path) {
		$var_name = $obj_name = $module."_".$script;
		
		if(substr($script, strlen($script) - 4) != ".php")
			$var_name .= ".php";
		else
			$obj_name = substr($obj_name, 0, strlen($obj_name) - 4);
		
		if(!isset($this->scripts[$var_name])) {
			require($path);
			
			if(!class_exists($obj_name)) 
				throw new wf_exception(
					$this,
					WF_EXC_PRIVATE,
					"Object <strong>$name</strong> is not declared in file $path"
				);
			
			$this->scripts[$var_name] = $obj = new ${"obj_name"}($this, $this->args, $this->opts);
			
			if(get_parent_class($obj) != "wf_cli_command")
				throw new wf_exception(
					$this,
					WF_EXC_PRIVATE,
					"Object <strong>$obj</strong> must extends wf_cli_command"
				);
			
			if(!method_exists($obj, "process"))
				throw new wf_exception(
					$this,
					WF_EXC_PRIVATE,
					"Class ".get_class($obj)." must define a process() method."
				);
		}
		
		return $this->scripts[$var_name];
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * public console methods
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function msg($msg, $raw = false) {
		echo ($raw ? "" : "* ")."$msg\n";
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
	
	/* ask question */
	public function ask($title) {
		if(function_exists("readline")) {
			$line = readline("~# $title");
			if(!empty($line))
				readline_add_history($line);
		}
		else {
			echo "~# $title";
			$stdin = fopen("php://stdin", 'r');
			$line = trim(fgets($stdin));
			fclose($stdin);
		}
		
		return $line;
	}
	
	/* read the line */
	private function read() {
		if(function_exists("readline")) {
			$line = readline("~# ");
			if(!empty($line))
				readline_add_history($line);
		}
		else {
			echo "~# ";
			$stdin = fopen("php://stdin", 'r');
			$line = trim(fgets($stdin));
			fclose($stdin);
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
				if(strlen($arg) > 2 && substr($arg, 0, 2) == "--") {
					if(!empty($opt)) {
						$opts[$opt] = null;
						$opt = "";
					}
					$opt = substr($arg, 2);
				}
				elseif(strlen($arg) > 1 && $arg[0] == "-") {
					if(!empty($opt)) {
						$opts[$opt] = null;
						$opt = "";
					}
					$opts[] = substr($arg, 1);
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
		
		if(!empty($opt)) {
			$opts[$opt] = null;
			$opt = "";
		}
		
		$this->command = $line;
		$this->args = $args;
		$this->argc = count($args);
		$this->opts = $opts;
	}
	
	public function get_opt($name, $long = false) {
		return $long && isset($this->opts[$name]) ?
			$this->opts[$name] : in_array($name, $this->opts) || array_key_exists($name, $this->opts);
	}
	
	public function get_argc() {
		return $this->argc;
	}
	
	public function get_args($i = -1) {
		return isset($this->args[$i]) ? $this->args[$i] : $this->args;
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
