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

	private $wf   = null;
	private $dset = null;

	public function __construct($wf, $dset) {
		$this->wf   = $wf;
		$this->dset = $dset;
	}

	public function render($tpl_path) {
		$tpl = new core_tpl($this->wf);
		$tpl->set('filters',     $this->dset->gen_filters());
		$tpl->set('fields',      $this->dset->get_fields());
		$tpl->set('data',        $this->dset->gen_data());
		$tpl->set('form_filter', $this->dset->get_form_filter());
		return($tpl->fetch($tpl_path));
	}

}
