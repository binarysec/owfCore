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
 
class core_lang extends wf_agg {

	var $ini = NULL;
	
	var $current = NULL;
	var $available = NULL;
	
	public function loader($wf) {
		$this->wf = $wf;
		
		/** \todo SYSTEME DE CACHE */
		/* prend le fichier ini */
		$file = dirname(dirname(__FILE__))."/var/lang.ini";

		$this->ini = parse_ini_file($file, TRUE);
		
		/* charge les langues disponible */
		$t = explode(',', $this->wf->ini_arr["lang"]["available"]);
		foreach($t as $v)
			$this->available[$v] = TRUE;
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Vérification de la langue
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function check_lang_request() {
		$session = $this->wf->core_session();
		/* prend les données spécifiques à l'utilisateur */
		$lang = $session->get_data("language");
		if(!$lang) {
			$session->set_data(array(
				"language" => $this->wf->ini_arr["lang"]["default"]
			));
			$lang = $session->get_data("language");
		}
		
		/* set la langue */
		if(!$this->set($lang)) {
			if($this->wf->ini_arr["lang"]["default"] == $lang) {
				throw new wf_exception(
					$this,
					WF_EXC_PRIVATE,
					"Default language does not exists"
				);
			}
			
			/* force le passage de la langue */
			$session->set_data(array(
				"language" => $this->wf->ini_arr["lang"]["default"]
			));
			$lang = $session->get_data("language");
			$this->set($lang);
		}
	}
	
	public function set($lang) {
		/* vérification si les données sont bonnes */
		$this->current = $this->resolv($lang);

		if(!$this->current)
			return(FALSE);
	
		/* vérification si disponnible */
		if(!$this->available[$lang])
			return(FALSE);
		
		/* passage des informations de contenu et d'encodage */
		$html = $this->wf->core_html();
		$html->set_meta_http_equiv(
			"Content-Language",
			array(
				"Content" => $this->current["code"]
			)
		);
		$html->set_meta_http_equiv(
			"Content-Type",
			array(
				"Content" => "text/html charset=".
					$this->current["encoding"]
			)
		);
		
		/* set les elements par default */
		$request = $this->wf->core_request();
		$request->set_header("Content-Language", $this->current["code"]);
		$request->set_header("Content-Type", "text/html");
		
		return($this->current);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Fonction de translation
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function tf($text) {
	
	}
	
	public function get() {
		return($this->current);
	}
	
	public function get_list() {
		return($this->available);
	}
	
	public function resolv($lang) {
		if($this->ini[$lang])
			return($this->ini[$lang]);
		return(FALSE);
	}
	
	
	

	
}

?>