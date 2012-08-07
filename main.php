<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * Web Framework 1                                       *
 * BinarySEC (c) (2000-2008) / www.binarysec.com         *
 * Author: Michael Vergoz <mv@binarysec.com>             *
 * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~         *
 *  Avertissement : ce logiciel est protégé par la       *
 *  loi du copyright et par les traités internationaux.  *
 *  Toute personne ne respectant pas ces dispositions    *
 *  se rendra coupable du délit de contrefaçon et sera   *
 *  passible des sanctions pénales prévues par la loi.   *
 *  Il est notamment strictement interdit de décompiler, *
 *  désassembler ce logiciel ou de procèder à des        *
 *  opération de "reverse engineering".                  *
 *                                                       *
 *  Warning : this software product is protected by      *
 *  copyright law and international copyright treaties   *
 *  as well as other intellectual property laws and      *
 *  treaties. Is is strictly forbidden to reverse        *
 *  engineer, decompile or disassemble this software     *
 *  product.                                             *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * */


define("WF_VERSION",   "1.3.0");

define("WF_T_INTEGER", 1);
define("WF_T_DOUBLE",  2);
define("WF_T_STRING",  6);

define("WF_USER_GOD",     "session:god");
define("WF_USER_ADMIN",   "session:admin");
define("WF_USER_SIMPLE",  "session:simple");
define("WF_USER_SERVICE", "session:service");
define("WF_USER_ANON",    "session:anon");
define("WF_USER_RANON",   "session:ranon");

define("WF_ROUTE_ACTION",   1);
define("WF_ROUTE_REDIRECT", 2);
define("WF_ROUTE_MENU_ACTION", 3);
define("WF_ROUTE_MENU_REDIRECT", 4);

define("WF_ROUTE_SHOW", 1); // visible pour l'utilisateur
define("WF_ROUTE_HIDE", 2); // insivible pour l'utilisateur

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 *
 * To define a new route request
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
abstract class wf_route_request {
	var $wf = NULL;
	abstract public function __construct($wf);
}

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 *
 * To define a aggregated module (which is loaded only one time 
 * and it shared over the program)
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
abstract class wf_agg {
	var $wf = NULL;
	
	public function __construct(&$wf) {
		$this->wf = $wf;
	}
	
	public function __clone() {
		throw new wf_exception(
			$this,
			WF_EXC_PRIVATE,
			"Can not clone aggregator for ".__CLASS__
		);
	}
}

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 *
 * Object module dependance
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
abstract class wf_module {
	public $wf = NULL;
	public $lang = NULL;
	
	public function __construct($wf) {
		$this->wf = $wf;
	}
	
	
	public function ts($text) {
		if(!$this->lang) {
			$ctxname = "module/".$this->get_name();
			$this->lang = $this->wf->core_lang()->get_context($ctxname);
		}
		return($this->lang->ts($text));
	}
	
	abstract public function get_name();
	abstract public function get_description();
	
	abstract public function get_banner();
	
	abstract public function get_version();
	
	abstract public function get_authors();
	
	/* return an array corresponding to the list of available modules */
	abstract public function get_depends();
	
	/* 
	 * data structure must be returned by get_actions()
	 * si TYPE = WF_ROUTE_ACTION alors :
	 * array(
	 * 	"/link/name" => array(
	 * 		// type of the route
	 * 		"TYPE",
	 * 		// name of the function which will be 
	 * 		// used to execute the function
	 * 		"MODULE_NAME", 
	 * 		// name of the function which will be 
	 * 		// executed for the link
	 * 		"FUNCTION_NAME", 
	 * 		// text printed, will be translated
	 * 		"TF_TEXT", 
	 *		// route is hidden or not ? WF_ROUTE_SHOW, WF_ROUTE_HIDE
	 *		"ROUTE_VIEW"
	 * 		// serial privileges WF_USER_GOD, *_ADMIN, *_SIMPLE, *_ANON
	 * 		"SERIAL,PRIVILEGE,ASSIGNED" 
	 * 	)
	 * )
	 * si TYPE = WF_ROUTE_REDIRECT alors :
	 * array(
	 * 	"/link/name" => array(
	 * 		// type of the route
	 * 		"TYPE",
	 * 		// link to redirect
	 * 		"LINK_NAME", 
	 * 		// text printed, will be translated
	 * 		"TF_TEXT", 
	 *		// route is hidden or not ? WF_ROUTE_SHOW, WF_ROUTE_HIDE
	 *		"ROUTE_VIEW"
	 * 	)
	 * )
	 */
	abstract public function get_actions();
}

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 *
 * Exception manager
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
define("WF_EXC_PUBLIC", 1);
define("WF_EXC_PRIVATE", 1);

class wf_exception extends Exception {
	var $wf;
	var $type;
	var $messages;
	
	public function __construct($wf, $type, $messages) {
		$this->wf = $wf;
		$this->type = $type;
		$this->messages = $messages;
		$this->load_message();
	}
	
	public function load_message() {
// 		if($this->wf->ini_arr["common"]["show_backtrace"]) {
			if(!is_array($this->messages)) {
				$msg = array($this->messages);
				$this->messages = $msg;
			}
			
			$this->messages[] = "Show backtrace activated into ini file :";

			$debug = debug_backtrace();
			foreach($debug as $v) {
				if(!isset($v["file"]))
					$v["file"] = __FILE__;
				if(!isset($v["line"]))
					$v["line"] = "??";
					
				if(isset($v["object"])) {
					$this->messages[] = 
						"<b>$v[file]</b>".
						":+$v[line] ".
						"<u><b>$v[class]::$v[function]()</b></u>";
				}
			}
// 		}

		return($this->messages);
	}
}

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 *
 * Base of the framework
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
class web_framework {
	var $ini_arr;
	var $db;
	private $_core_log = null;
	public $time_start;
	
	private $aggregator_cached = array();

	public function __construct($ini, $db=true) {
		/* record starting time */
		$this->time_start = microtime(TRUE);
		
		/* load the ini file */
		$this->load_by_file($ini);
		
		/* load initial modules given from ini file */
		$save = getcwd();
		chdir(dirname($ini));
		foreach($this->ini_arr["modules"] as $name => $dir) {
			$rdir = realpath($dir);
			$this->load_module($name, $rdir);
		}
		chdir($save);
		
		/* put lang context */
// 		$l = $this->core_lang();
// 		foreach($this->modules as $name => $mod) {
// 			echo "$name<br>\n";
// 			$ctxname = "module/$name";
// // 			$mod[8]->lang = 
// 		}
// 		exit(0);
		
// 			$this->modules[$name] = array(
// 				$dir,
// 				$name,
// 				$obj->get_name(),
// 				$obj->get_description(),
// 				$obj->get_banner(),
// 				$obj->get_version(),
// 				$obj->get_authors(),
// 				$obj->get_depends(),
// 				$obj
// 			);
			
		/* fonction d'autoloader */
		spl_autoload_register(array($this, 'autoloader'));
		
		/* Open database */
		if($db == true)
			$this->open_db();
			
		$this->execute_hook("owf_post_init");
	
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Master processing
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function log($log) {
		if(!$this->_core_log)
			$this->_core_log = $this->core_log();
			
		$this->_core_log->log($log);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Master processing
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function process() {
		/* check language */
		$l = $this->get_lang_code();
		$this->core_lang()->check_lang_route($l);
		
		/* chargement des routes */
		$this->core_route()->scan();
		
		/* traitement de la requete */
		$this->core_request()->process();
	}

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Get language code
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	private function get_lang_code() {
		if(!isset($_SERVER["PATH_INFO"]))
			return(NULL);
		
		$t = explode("/", $_SERVER["PATH_INFO"]);
		return(isset($t[1]) ? $t[1] : NULL);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Load global ini configuration
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function load_by_file($file) {
		if(function_exists("apc_fetch") && ($i = apc_fetch("owf_ini".$file)))
			$this->ini_arr = unserialize($i);
		else {
			$this->ini_arr = @parse_ini_file($file, TRUE);
			if($this->ini_arr == NULL) {
				throw new wf_exception(
					$this,
					WF_EXC_PUBLIC,
					"Could not load general ini file"
				);
			}

			if(function_exists("apc_fetch"))
				apc_store("owf_ini".$file, serialize($this->ini_arr));
		}
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Application modules manager
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public $modules = array();
	
	public function load_module($name, $dir) {
		$modfile = $dir."/module.php";	
		if(file_exists($modfile)) {
			
			/* parse/vm the file */
			require_once($modfile);
			
			/* define the module name */
			$objname = "wfm_".$name;
			
			/* check if the class exists */
			if(!class_exists($objname)) {
				throw new wf_exception(
					$this,
					WF_EXC_PRIVATE,
					"could not load object $name: ".
					"doesn't exists"
				);
			}
			
			/* load the object */
			$obj = new ${"objname"}($this);
			
			/* sanatize */
			if(get_parent_class($obj) != "wf_module") {
				throw new wf_exception(
					$this,
					WF_EXC_PRIVATE,
					get_class($obj).
					" must be derived from wf_module, ".
					"check your code."
				);
			}
			
			/* register the new module */
			$this->modules[$name] = array(
				$dir,
				$name,
				$obj->get_name(),
				$obj->get_description(),
				$obj->get_banner(),
				$obj->get_version(),
				$obj->get_authors(),
				$obj->get_depends(),
				$obj
			);

		}
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Open the database
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function open_db() {
		$d = $this->ini_arr["db"];
		$driver = "core_db_".$d["driver"];
		$this->db = new ${"driver"};
		$this->db->wf = $this;
		$ret = $this->db->load($d);
		if($ret == FALSE) {
			throw new wf_exception(
				$this,
				WF_EXC_PUBLIC,
				"DB driver exception"
			);
		}
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Function used to execute a share event
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function execute_hook($name, $args=array(), $cb=NULL) {
		$result = array();
		/* execute filters */
		foreach($this->modules as $k => $mod) {
			/* function exists ? */
			if(method_exists($mod[8], $name)) {
				/* call the user function */
				$r = call_user_func_array(
					array($mod[8], $name),
					$args
				);
				
				/* call back user function to 
				   control result */
				if($cb) {
					call_user_func_array(
						$cb,
						$r
					);
				}
				
				$result[] = $r;
			}
		}
		return($result);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * PHP object autoloader
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function autoloader($classname) {
		$file = $this->locate_file("lib/".$classname.".php");
		if(!$file) {
			throw new wf_exception(
				$this,
				WF_EXC_PRIVATE,
				"Autoloader error: can not find $classname"
			);
		}
		
		/* chargement */
		require_once($file);
	}

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Modular aggregator
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function __call($funcname, $exception=TRUE) {
		if(isset($this->aggregator_cached[$funcname]))
			return($this->aggregator_cached[$funcname]);

		$file = $this->locate_file("agg/".$funcname.".php");
		if(!$file) {
			if($exception) {
				throw new wf_exception(
					$this,
					WF_EXC_PRIVATE,
					"Could not find the aggregated ".
					"function name $funcname"
				);
			}
			else
				return(NULL);
		}
		
		/* loading file */
		require($file);

		/* launching object */
		$obj = new ${"funcname"}($this);
		
		/* caching object */
		$this->aggregator_cached[$funcname] = &$obj;
		
		/* execute le chargeur */
		$obj->loader($this);
		
		return($obj);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Modular aggregator
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function load_agg($funcname) {
		$funcname = preg_replace(
			array(
				"/\//",
				"/\./"
			),
			array(),
			$funcname
		);
	
		if(isset($this->aggregator_cached[$funcname]))
			return($this->aggregator_cached[$funcname]);
		
		$file = $this->locate_file("agg/".$funcname.".php");
		if(!$file)
			return(FALSE);
		
		/* loading file */
		require($file);

		/* launching object */
		$obj = new ${"funcname"}($this);
		
		/* caching object */
		$this->aggregator_cached[$funcname] = &$obj;
		
		/* execute le chargeur */
		$obj->loader($this);

		return($obj);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Function used to get the rail
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function get_rail() {
		return(isset($_SERVER["PATH_INFO"]) ? $_SERVER["PATH_INFO"] : NULL);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Function to check if module exists
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function mod_exists($name) {
		return(is_array($this->modules[$name]));
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Use to link
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function linker($route, $filter=NULL, $lang_code=NULL, $forwarder=FALSE) {
	
		$cl = $this->core_lang()->resolv($lang_code);
		if(!$cl)
			$lang_code = $this->core_lang()->get_code();
		else
			$lang_code = $cl["code"];

		if(strncmp('http://', $route, 7) == 0)
			return($route);
			
		/* encode le lang into the link */
		$n_route = "/$lang_code$route";
		
		$base = "";
		if(isset($this->ini_arr["common"]["base"]))
			$base = $this->ini_arr["common"]["base"];
		
		$query = NULL;
		if($forwarder && !empty($_SERVER["QUERY_STRING"]))
			$query = "?".$_SERVER["QUERY_STRING"];
		
		return(
			$base."/index.php".
			$n_route.
			$query
		);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Function use to create directories recursively
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function create_dir($filename, $ignore_file=TRUE) {
		$tab = explode("/", $filename);
		$dir = "/";

		$skip = $ignore_file ? 1 : 0;
		
		for($a=1; $a<count($tab)-$skip; $a++) {
			$v = &$tab[$a];
			$dir .= "$v/";
			if(!is_dir($dir) && !@mkdir($dir)) {
				$error = error_get_last();
				throw new wf_exception(
					$this,
					WF_EXC_PRIVATE,
					"Can not create : $dir / $error[message]"
				);
			}
		}
		
		return(TRUE);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Function use to remove directories recursively
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function remove_dir($dir) {
		if (!file_exists($dir)) 
			return true;
		if (!is_dir($dir) || is_link($dir)) 
			return unlink($dir);
		foreach (scandir($dir) as $item) {
			if ($item == '.' || $item == '..') continue;
			if (!$this->remove_dir($dir . "/" . $item)) {
			chmod($dir . "/" . $item, 0777);
			if (!$this->remove_dir($dir . "/" . $item)) return false;
			};
		}
		return(rmdir($dir));
	} 
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Get a variable
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function get_var($name) {
		$var = null;

		if(isset($_GET[$name]))
			$var = $_GET[$name];
		else if(isset($_POST[$name]))
			$var = $_POST[$name];
		else if(isset($_FILES[$name]))
			$var = $_FILES[$name];

		if(is_array($var) && isset($_FILES[$name]) && is_array($_FILES[$name])) {
			$var = array_merge($var, $_FILES[$name]);
		}

		return($var);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Display the error page
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function display_error($code, $message) {
	
		/* add display login hooker */
		$this->execute_hook("owf_display_error", array($code, $message));
		
		header("HTTP/1.1 $code $message");
		$tpl = new core_tpl($this);
		$tpl->set(
			"message",
			$message
		);
		$tpl->set(
			"code",
			$code
		);
		$html = $this->core_html();
		$html->set_title($message);
		$html->rendering($tpl->fetch("core/html/error"));
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Give you the link of dialog page
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function dialog($options) {
		
	}
	
	public function redirector($url) {
		$tpl = new core_tpl($this);
		$tpl->set("url", $url);
		echo $tpl->fetch("core/html/redirect");
		exit(0);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Display login
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function display_login($message=NULL, $vars=null, $back=false) {
		$ci = $this->core_cipher();
		
		$this->no_cache();
		
		/* add display login hooker */
		$this->execute_hook("owf_display_login",array($message));
		
		$tpl = new core_tpl($this);
		if($vars)
			$tpl->set_vars($vars);
		
		if($message != NULL) {
			$tpl->set(
				"message", 
				$message
			);
		}
		
		/* generate back URL */
		$back_url = null;
		if($back)
			$back_url = $ci->encode($_SERVER["REQUEST_URI"]);
		else {
			$back = $ci->get_var('back_url');
			if(strlen($back) > 0)
				$back_url = $ci->encode($back);
			else
				$back_url = $ci->encode($this->linker("/admin"));
		}
		$tpl->set("back_url", $back_url);
		
		/* encode my url */
		$tpl->set("here_url", $ci->encode($this->linker($_SERVER["REQUEST_URI"])));
		
		/* get account & password */
		$tpl->set("allow_account_creation", $this->ini_arr['session']['allow_account_creation']);
		$tpl->set("allow_pass_recovering", $this->ini_arr['session']['allow_pass_recovering']);
		
		if(isset($_SERVER["HTTP_X_REAL_IP"])) {
			$tpl->set(
				"via_ip", 
				htmlspecialchars($_SERVER["REMOTE_ADDR"])
			);
			$tpl->set(
				"via_addr", 
				gethostbyaddr($_SERVER["REMOTE_ADDR"])
			);
		
			$tpl->set(
				"remote_ip", 
				htmlspecialchars($_SERVER["HTTP_X_REAL_IP"])
			);
			$tpl->set(
				"remote_addr", 
				gethostbyaddr($_SERVER["HTTP_X_REAL_IP"])
			);
		}
		else if(isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
			$tpl->set(
				"via_ip", 
				htmlspecialchars($_SERVER["REMOTE_ADDR"])
			);
			$tpl->set(
				"via_addr", 
				gethostbyaddr($_SERVER["REMOTE_ADDR"])
			);
		
			$tpl->set(
				"remote_ip", 
				htmlspecialchars($_SERVER["HTTP_X_FORWARDED_FOR"])
			);
			$tpl->set(
				"remote_addr", 
				gethostbyaddr($_SERVER["HTTP_X_FORWARDED_FOR"])
			);
		}
		else {
			$tpl->set(
				"remote_ip", 
				htmlspecialchars($_SERVER["REMOTE_ADDR"])
			);
			$tpl->set(
				"remote_addr", 
				gethostbyaddr($_SERVER["REMOTE_ADDR"])
			);
		}
		
		header("X-Owf-Session: NeedAuth");
		echo $tpl->fetch("core/html/login");
		exit(0);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Return the 8bit scale
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function bit8_scale($sz, $init_pow=0, $base=1024, $g="Go", $m="Mo", $k="Ko", $o="Octets") {
		if($sz >= pow($base, 3+$init_pow))
			return(sprintf("%.2f $g", ($sz/pow($base, 3+$init_pow))));
		else if($sz >= pow($base, 2+$init_pow))
			return(sprintf("%.2f $m", ($sz/pow($base, 2+$init_pow))));
		if($sz >= $base) 
			return(sprintf("%.2f $k", ($sz/$base)));
		else
			return($sz." $o");
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Scan a directory
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function scandir($path, $assoc=array()) {
		$modrev = array_reverse($this->modules);
		$list = array();
		
		foreach($modrev as $mod => $mod_infos) {
			$tmp = $this->modules[$mod][0].
				"$path";
			$dirs = @scandir($tmp);
			if(file_exists($tmp)) {
				foreach($dirs as $dir) {
					if(!isset($assoc[$dir])) {
						$assoc[$dir] = $tmp;
						$list[] = $dir;
					}
					else
						$assoc[$dir] = array(
							$assoc[$dir],
							$tmp
						);
				}
			}
		}

		return($list);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Locate a file with priority respect
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function locate_file($filename, $return_array=FALSE) {
		$file = NULL;
		$modrev = array_reverse($this->modules);

		foreach($modrev as $mod => $mod_infos) {
			$tmp = $this->modules[$mod][0].
				"/$filename";
			if(file_exists($tmp)) {
				if($return_array) {
					$file = array(
						$tmp,
						&$this->modules[$mod][0],
						$this->modules[$mod][1]
					);
				}
				else
					$file = $tmp;
				
				break;
			}
		}
		if(!$file)
			return(NULL);
		return($file);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Safe descriptor write
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	function safe_write($fp, $string) {
		for($written = 0; $written < strlen($string); $written += $fwrite) {
			$fwrite = fwrite($fp, substr($string, $written));
			if($fwrite == false)
				return(false);
		}
		return($written);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Safe descriptor write for socket
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	function safe_sockwrite($fp, $string) {
		for($written = 0; $written < strlen($string); $written += $fwrite) {
			$fwrite = socket_write($fp, substr($string, $written));
			if($fwrite == false)
				return(false);
		}
		return($written);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Get the last priority filename
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function get_last_filename($filename) {
		$mod = end($this->modules);
		$file = "$mod[0]/$filename";
		return($file);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Get an ini argument
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function get_ini($where, $key=NULL) {
		if($key)
			return($this->ini_arr[$where][$key]);
		return($this->ini_arr[$where]);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Function used to get a random buffer from available interface
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	private $rfd = NULL;
	public function get_rand($size=1024) {
		if(!$this->rfd)
			$this->rfd = fopen("/dev/urandom", "r");
		$b = fread($this->rfd, $size);
		return($b);
	}
	
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Use as a hash standard
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function hash($data) {
		return(sha1($data));
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Rendomize letters
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function random_letters($rot=3) {
		$buf = NULL;
		for($a=0; $a<$rot; $a++)
			$buf .= chr(rand(0x41, 0x5A));
		return($buf);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Generate good password letters
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public $pass_tab = array('!', '_', '-', '%', '*', '=');
	public function generate_password($rot=10, $no_special=false) {
		$t = 100;
		$buf = NULL;
		for($a=0; $a<$rot; $a++) {
			$swt = rand(0, $t);
			if($swt < $t/4)
				$buf .= chr(rand(0x31, 0x39));
			else if($swt <= $t/4*1.5 && $no_special == false) {
				$p = rand(0, count($this->pass_tab)-1);	
				$buf .= $this->pass_tab[$p];
			}
			else if($swt <= $t/4*2) {
				while(1) {
					$ch = chr(rand(0x41, 0x5A));
					if($ch != 'O')
						break;
				}
				$buf .= $ch;
			}
			else if($swt <= $t/4*4) {
				while(1) {
					$ch = chr(rand(0x61, 0x7A));
					if($ch != 'O')
						break;
				}
				$buf .= $ch;
			}
		}
		return($buf);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Function use to define a non cachable request
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	private $no_cache_set = false;
	public function no_cache() {
		if($this->no_cache_set == false)
			$this->no_cache_set = true;
		else
			return(true);
			
		header("Expires: -1");
		header("Cache-Control: private, max-age=0");
		header("Pragma: no-cache");
		return(true);
	}
	
	
/*
get_first_filename
locate_files
*/

}
