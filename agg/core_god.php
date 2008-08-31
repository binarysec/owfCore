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

class core_god extends wf_agg {
	var $_core_lang;
	public function loader($wf) {
		$this->wf = $wf;
		$this->_core_html = $wf->core_html();
		$this->_core_lang = $wf->core_lang();
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Permet de recuperer le contenu managé
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function get_content() {
		$tpl = new core_tpl($this->wf);
		$tpl->set('tpl_edit', $this->get_template());
		
		return($tpl->fetch('core/god/body', TRUE));
		return(NULL);
	}
	
	private function get_template() {
		$buf = NULL;
		foreach($this->_core_html->managed_list as $val) {
			$data = null;

			if(file_exists($val[1]->tpl_file))
				$data = file_get_contents($val[1]->tpl_file);
	
			/* edit template */
			$tpl = new core_form($this->wf, "god_tpl_edit");
			
			$fa1 = new core_form_textarea('text');
			$fa1->value = $data;
			$tpl->add_element($fa1);
		
			$fs1 = new core_form_submit('submit');
			$fs1->value = 'Enregistrer';
			$tpl->add_element($fs1);
		
			$tpl->tpl_name = $val[0];
			
// 			foreach($this->_core_lang->get_list() as $k => $v) {
// 			
// 			}
			
			$buf .= $tpl->render('core/god/tpl_edit', TRUE);
		}

		return($buf);
	}
	
	
	
}