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

class core_html extends wf_agg {
	var $constants = array();
	
	public function loader($wf, $position) {
		$this->wf = $wf;
	}

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Ajoute une donnée de rendement par rapport à la constance
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function set($constant, $data) {
		$this->constants[$constant] = $data;
		return(TRUE);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Prend une donnée de rendement
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function get($constant) {
		return($this->constants[$constant]);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Lance le systeme de rendu et retourne les données
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function rendering($tpl) {
// 		if(!file_exists($file)) {
// 			throw new wf_exception(
// 				$this,
// 				WF_EXC_PRIVATE,
// 				array(
// 					"Template $file doesn't exists"
// 				)
// 			);
// 		}
// 
// 		/* preparation des patterns & remplacements */
// 		$patterns = array();
// 		$replacements = array();
// 		foreach($this->constants as $key => $value) {
// 			$patterns[] = "/%$key%/";
// 			$replacements[] = $value;
// 		}
// 		$patterns[] = "/%[A-Za-z_0-9]+%/";
// 		
// 		/* prend le contenu du fichier */
// 		$data = file_get_contents($file);
// 		
// 		/* remplace les données */
// 		$ret = preg_replace($patterns, $replacements, $data);
// 		
// 		return($ret);
		echo "rendering $tpl";
		exit(0);
	}
	
	
}