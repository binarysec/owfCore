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

define("WF_T_INTEGER", 1);
define("WF_T_DOUBLE",  2);
define("WF_T_STRING",  6);

define("WF_RENDER_LOGIN", 1);
define("WF_RENDER_SHOW",  2);
define("WF_RENDER_SHOW",  2);
define("WF_RENDER_ERROR", 3);


/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 *
 * To define information in relation with the route.
 * Permissions and menus are defined here.
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
abstract class wf_route_info {
	var $wf = NULL;
	abstract public function __construct($wf);
	
	/* 
	 * data structure must be returned by get_info()
	 * array(
	 * 	// nom du module
	 * 	"MODULE_NAME",
	 * 	// description du module
	 * 	"MODULE_DESCRIPTION",
	 * 	// personne/societe supportant le module
	 * 	"MODULE_SUPPORT",
	 * 	// url du supporter
	 * 	"MODULE_SUPPORT_URL"
	 * )
	 */
	abstract public function get_info();
	
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
	abstract public function loader($wf, $position);
}

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 *
 * Object module dependance
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
abstract class wf_module {
	var $wf = NULL;
	abstract public function __construct($wf);
	
	abstract public function get_name();
	abstract public function get_description();
	
	abstract public function get_banner();
	
	abstract public function get_version();
	
	abstract public function get_authors();
	
	/* return an array corresponding to the list of available modules */
	abstract public function get_depends();
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
		if($this->wf->ini_arr["common"]["show_backtrace"]) {
			if(!is_array($this->messages)) {
				$msg = array($this->messages);
				$this->messages = $msg;
			}
			
			$this->messages[] = "Show backtrace activated into ini file :";

			$debug = debug_backtrace();
			foreach($debug as $v) {
				if(!$v["file"])
					$v["file"] = __FILE__;
				if(!$v["line"])
					$v["line"] = "??";
					
				if($v["object"]) {
					$this->messages[] = 
						"<b>$v[file]</b>".
						":+$v[line] ".
						"<u><b>$v[class]::$v[function]()</b></u>";
				}
			}
		}

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
	
	public function __construct($ini) {
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
		
		/* fonction d'autoloader */
		spl_autoload_register(array($this, 'autoloader'));
	}
	
	public function process(
		) {
		/* Open database */
		$this->open_db();
		
		/* chargement des routes */
		$this->core_route()->scan();
		
		/* traitement de la requete */
		$this->core_request()->process();
	}

	protected function preinit() {
		//
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Load global ini configuration
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function load_by_file($file) {
		$ret = @parse_ini_file($file, TRUE);
		if($ret == NULL) {
			throw new wf_exception(
				$this,
				WF_EXC_PUBLIC,
				"Could not load general ini file"
			);
		}
		$this->ini_arr = $ret;
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Application modules manager
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	var $modules = array();
	public function load_module($name, $dir) {
		$modfile = $dir."/module.php";

		if(file_exists($modfile)) {
			/* parse/vm the file */
			require($modfile);
			
			if(!class_exists($name)) {
				throw new wf_exception(
					$this,
					WF_EXC_PRIVATE,
					"could not load object $name: ".
					"doesn't exists"
				);
			}
			
			/* load the object */
			$obj = new ${name}($this);
			
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
				$obj->get_depends()
			);
		}
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Open the database
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	private function open_db() {
		$d = $this->ini_arr["db"];
		$driver = "core_db_".$d["driver"];
		$this->db = new ${driver};
		$this->db->waf = $this;
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
	 * PHP object autoloader
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function autoloader($classname) {
		$done = FALSE;
		foreach($this->modules as $modname => $info) {
			$dir = &$info[0];
			$file = $dir."/lib/".$classname.".php";
			if(file_exists($file)) {
				$done = TRUE;
				break;
			}
		}
		if(!$done) {
			throw new wf_exception(
				$this,
				WF_EXC_PRIVATE,
				"Autoloader error: can not find $classname"
			);
		}
		
		/* chargement */
		require($file);
	}

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Modular aggregator
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	var $aggregator_cached = array();
	public function __call($funcname, $args = array()) {
		if($this->aggregator_cached[$funcname])
			return($this->aggregator_cached[$funcname]);

		$done = FALSE;
		foreach($this->modules as $modname => $info) {
			$dir = &$info[0];
			$file = $dir."/agg/$funcname.php";
			if(file_exists($file)) {
				$done = TRUE;
				break;
			}
		}
		if(!$done) {
			throw new wf_exception(
				$this,
				WF_EXC_PRIVATE,
				"Could not find the aggregated function name $funcname"
			);
		}
		
		/* loading file */
		require($file);
		
		/* launching object */
		$obj = new ${funcname};
		
		/* caching object */
		$this->aggregator_cached[$funcname] = $obj;
		
		/* execute le chargeur */
		$obj->loader($this, 0);
		
		return($obj);
	}
	
	
}


?>