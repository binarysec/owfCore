<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * OpenWF - Open source Web Framework                    *
 * BinarySEC (c) (2000-2009) / www.binarysec.com         *
 * Author: Michael Vergoz <mv@binarysec.com>             *
 * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~         *
 *  Avertissement : ce logiciel est protégé par la       *
 *  loi du copyright et par les traités internationaux.  *
 *  Toute personne ne respectant pas ces dispositions    *
 *  se rendra coupable du délit de contrefaçon et sera   *
 *  passible des sanctions pénales prévues par la loi.   *
 *  Il est notamment strictement interdit de décompiler, *
 *  désassembler ce logiciel ou de procèder à des        *
 *  opération de 'reverse engineering'.                  *
 *                                                       *
 *  Warning : this software product is protected by      *
 *  copyright law and international copyright treaties   *
 *  as well as other intellectual property laws and      *
 *  treaties. Is is strictly forbidden to reverse        *
 *  engineer, decompile or disassemble this software     *
 *  product.                                             *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

class core_dataview {

	private $fw   = null;
	private $dset = null;
	private $args = array();
	private $form_responder = null;
	private $total_filterless = false;
	
	public function __construct($wf, $dset) {
		$this->wf   = $wf;
		$this->dset = $dset;
	}
	
	public function add_argument($var, $val) {
		$this->args[$var] = $val;
	}
	
	public function set_form_responder($name) {
		$this->form_responder = $name;
	}
	
	public function set_total_filterless($val = true) {
		$this->total_filterless = $val;
	}
	
	public function render($tpl_path=NULL, $tplset=array()) {
		/* default template */
		if(!$tpl_path)
			$tpl_path = "core/dataset";
			
		/* création du template */
		$tpl = new core_tpl($this->wf);
		
		/* ajout des variables utilisateur */
		foreach($tplset as $k => $v)
			$tpl->set($k, $v);
			
		/* ajout des variables interne */
		$tpl->set('name',    $this->dset->get_name());
		$tpl->set('cols',    $this->dset->get_cols());
		$tpl->set('rows',    $this->dset->get_rows());
		$tpl->set('filters', $this->dset->get_filters());
		$tpl->set('page_nb', $this->dset->get_page_nb());
		$tpl->set('display_dataset_select_bar', $this->dset->get_display_select_bar());
		$tpl->set('args', $this->args);
		$tpl->set('form_responder', $this->form_responder);
		$tpl->set('rows_per_page',  $this->dset->get_rows_per_page());
		$tpl->set('range_rows_per_page',    $this->dset->get_range_rows_per_page());
		$tpl->set('total_num_rows', $this->dset->get_total_num_rows());
		if($this->total_filterless)
			$tpl->set('total_num_rows_filterless', $this->dset->get_total_num_rows_filterless());
		else
			$tpl->set('total_num_rows_filterless', $tpl->get('total_num_rows'));
		$tpl->set('form_order',     $this->wf->get_var($this->dset->get_name().'_order'));
		$tpl->set('form_filter',    $this->wf->get_var($this->dset->get_name().'_filter'));
		$tpl->set('form_page',      $this->wf->get_var($this->dset->get_name().'_page'));
		return($tpl->fetch($tpl_path));
	}

}
