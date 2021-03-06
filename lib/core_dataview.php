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
	
	var $data_role = "listview";
	var $data_inset = "true";
	var $data_mini = "true";
	
	private $fw   = null;
	private $dset = null;
	private $args = array();
	private $form_responder = null;
	
	var $total_ignore_conds = true;
	var $total_ignore_preconds = false;
	
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
		$this->total_ignore_conds = $val;
		$this->total_ignore_preconds = $val;
	}
	
	public function render($tpl_path = null, $tplset = array(), $tpl_panel = null) {
		/* default template */
		if(!$tpl_path)
			$tpl_path = "core/dataset";
		
		if(!$tpl_panel)
			$tpl_panel = "core/dataset_filters";
			
		/* création du template */
		$tpl = new core_tpl($this->wf);
		
		/* ajout des variables utilisateur */
		foreach($tplset as $k => $v)
			$tpl->set($k, $v);
		
		$cols = $this->dset->get_cols();
		
		/* prepare search */
		$searchi = 0;
		foreach($cols as $col) {
			if(array_key_exists('search', $col) && $col["search"])
				$searchi++;
		}
		
		$filters = $this->dset->get_filters();
		$orders = $this->dset->get_orderable_cols();
		
		/* ajout des variables interne */
		$tpl->set('here', $this->wf->linker($this->wf->core_request()->get_uri()));
		$tpl->set('name',    $this->dset->get_name());
		$tpl->set('cols',    $cols);
		$tpl->set('rows',    $this->dset->get_rows());
		$tpl->set('search',    $this->dset->get_search());
		$tpl->set('searchi',    $searchi);
		$tpl->set('filters', $filters);
		$tpl->set('orders', $orders);
		$tpl->set('page_nb', $this->dset->get_page_nb());
		$tpl->set('display_dataset_select_bar', $this->dset->get_display_select_bar());
		$tpl->set('args', $this->args);
		$tpl->set('form_responder', $this->form_responder);
		$tpl->set('rows_per_page',  $this->dset->get_rows_per_page());
		$tpl->set('min_rows_per_page', min($this->dset->get_range_rows_per_page()));
		$tpl->set('range_rows_per_page',    $this->dset->get_range_rows_per_page());
		$tpl->set('total_num_rows', $this->dset->get_total_num_rows());
		$tpl->set('total_num_rows_filterless', $this->dset->get_total_num_rows($this->total_ignore_conds, $this->total_ignore_preconds));
		$tpl->set('form_order',     $this->wf->get_var($this->dset->get_name().'_order'));
		$tpl->set('form_filter',    $this->wf->get_var($this->dset->get_name().'_filter'));
		$tpl->set('data_role', $this->data_role);
		$tpl->set('data_inset', $this->data_inset);
		$tpl->set('data_mini', $this->data_mini);
		
		/* panel stuff */
		if($filters || $orders) {
			$key = $this->wf->admin_html()->seed.$this->dset->get_name();
			$tpl->set('panelkey', $key);
			$panelid = $this->wf->admin_html()->add_panel(
				$tpl->fetch($tpl_panel),
				array(
					"data-position" => "left",
					"data-display" => "overlay"
				),
				$key
			);
			$tpl->set('panelid', $panelid);
		}
		
		return($tpl->fetch($tpl_path));
	}

}
