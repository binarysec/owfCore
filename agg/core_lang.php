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

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 *
 * This object must be loaded trought core_lang()->get_context()
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
class core_lang_context {
	public $full;
	public $file;
	public $lang;
	
	public $keys = array();
	
	private $rewrite = FALSE;
	
	public function __construct($lang, $full, $file) {
		$this->lang = $lang;
		$this->full = $full;
		$this->file = $file;
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Translation function
	 * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function ts($text) {
		if(empty($text))
			return false;
		
		if(is_array($text)) {
			$rtext = $text[0];
			unset($text[0]);
			$res = vsprintf(
				$this->get_translation($rtext), 
				$text
			);
			return($res);
		}
		
		return($this->get_translation($text));
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Used to write 
	 * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function change($key, $value) {
		$this->keys[$key] = $value;
		return(TRUE);
	}

	/* * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Used to read 
	 * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function get($key) {
		return($this->keys[$key]);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * key translation
	 * * * * * * * * * * * * * * * * * * * * * * * * * * */
	private function get_translation($text) {
		$key = base64_encode($text);
		
		$ktext = &$this->keys[$key];
		if(!$ktext) {
			$ktext = $text;
			$this->rewrite = TRUE;
		}
		return($ktext);
	}

}

class core_lang extends wf_agg {
	var $ini = NULL;
	
	var $current = NULL;
	var $available = NULL;
	
	private $_core_cacher;
	private $_core_register;
	
	public function loader($wf) {
		
		/** \todo SYSTEME DE CACHE */
		/* prend le fichier ini */
		$file = $this->wf->locate_file("var/lang.ini");

		$this->ini = parse_ini_file($file, TRUE);
		
		/* charge les langues disponibles */
		$t = explode(',', $this->wf->ini_arr["lang"]["available"]);
		foreach($t as $v)
			$this->available[$v] = $this->ini[$v];

		$this->_core_cacher = $this->wf->core_cacher();
		$this->_core_register = $this->wf->core_register();
		
		$this->default = $this->resolv($this->wf->ini_arr["lang"]["default"]);
	}
	
	public function set($lang) {
		/* vérification si les données sont bonnes */
		$this->current = $this->resolv($lang);

		if(!$this->current)
			return(FALSE);
	
		/* vérification si disponible */
		if(!$this->available[$lang])
			return(FALSE);
		
		/* passage des informations de contenu et d'encodage */
		$html = $this->wf->core_html();
		$html->set_meta_http_equiv(
			"Content-Language",
			array(
				"content" => $this->current["code"]
			)
		);
		$html->set_meta_http_equiv(
			"Content-Type",
			array(
				"content" => "text/html; charset=".$this->current["encoding"],
#				"charset" => $this->current["encoding"]
			)
		);
		
		/* set les elements par defaut */
		$request = $this->wf->core_request();
		$request->set_header(
			"Content-Language", 
			$this->current["code"]
		);
		$request->set_header(
			"Content-Type", "text/html"
		);
		
		/* force le passage de la langue */
		$this->_core_register->set_user_data(array(
			"language" => $lang
		));
		
		return($this->current);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Get a translation context
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public $contexts = array();
	public function get_context($name, $lang=NULL, $create=TRUE) {
		if($lang == NULL)
			$lang = $this->current["code"];
			
		/* get the full context path */
		$full = "var/lang/ctx/".
			$lang.
			"/".
			$name;
		
		if(isset($this->contexts[$full]))
			return($this->contexts[$full]);
			
		/* locate file */
		$file = $this->wf->locate_file($full, false, "f");
		
		/* if file exists try to unserialize it*/
		if($file) {
			$obj = unserialize(file_get_contents(
				$file
			));
			if(is_object($obj)) {
				$this->contexts[$full] = &$obj;
// 				echo "<pre>";
// 				var_dump($obj);
				return($this->contexts[$full]);
			}
		}
		
		if($create) {
			/* if no file found create a virtual one */
			if(!$file)
				$file = $this->wf->get_last_filename($full);
			
			/* create context */
			$this->contexts[$full] = new core_lang_context(
				$lang, 
				$name, 
				$full
			);
		}
		else
			return(NULL);
			
		return($this->contexts[$full]);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Check if a lang has been coded into the route
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function check_lang_route($str) {
		/* change language if possible */
		if(isset($this->available[$str])) {
			$this->set($str);
			return(TRUE);
		}
	
		if(!$this->available[$this->wf->ini_arr["lang"]["default"]]) {
			throw new wf_exception(
				$this,
				WF_EXC_PRIVATE,
				"Default language does not exists"
			);
		}
			
		if(!$this->current) 
			$this->set($this->wf->ini_arr["lang"]["default"]);
		
		return(FALSE);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Get current lang information
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function get() {
		return($this->current);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Get current code
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function get_code() {
		return($this->current["code"]);
	}

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Get default lang information
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function get_default() {
		return($this->default);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Get current code
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function get_default_code() {
		return($this->default["code"]);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Get the list of available languages
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function get_list() {
		return($this->available);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Resolv information in relation with the language
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function resolv($lang) {
		if(isset($this->ini[$lang]))
			return($this->ini[$lang]);
		return(FALSE);
	}

	
}

// svn merge -r83:81
