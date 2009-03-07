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

/*
TODO: regarder les slides 
filtre:
	- nom du champs de la zone = array()
		- traduction SELECT / SLIDE / ACTIVATE
		
		- si SELECT 
			- description = texte
			- traduction = array()
				nom en base = traduction
			
		- si ACTIVATE
			- description = texte
			- on = contrainte base
			- off = contrainte base
		
		
order = array()
	liste des champs de la zone 

vue des champs = array()
	- nom du champs de la zone
		- description
		% callback par ligne
		
champs de control = array()
	- array()
		- nom du head
		- défini si le champs est libre (graphiquement)
		- type CALLBACK / TEXT
		
		- si CALLBACK = callback
		- si TEXT = texte à afficher
		
*/

define('WF_CORE_DATASET_SELECT_FILTER',   1);
define('WF_CORE_DATASET_SLIDE_FILTER',    2);
define('WF_CORE_DATASET_ACTIVATE_FILTER', 3);

class core_dataset {

	private $wf      = null;
	private $dsrc    = null;
	private $filters = array();
	private $order   = array();
	private $fields  = array();
	private $fields_callback = array();
	private $form_filter     = array();

	public function __construct($wf, $dsrc) {
		$this->wf  = $wf;
		$this->dsrc = $dsrc;
	}

	/* set filters */
	public function set_filters($filters) {
		$this->filters = $filters;

		/* apply conditions */
		$this->form_filter = $this->wf->get_var('filter');
		$conds = array();
		foreach($this->filters as $col => $conf) {
			if($this->form_filter[$col]) {
				if($conf['type'] == WF_CORE_DATASET_SELECT_FILTER) {
					$conds[] = array($col, '==', $this->form_filter[$col]);
				}
			}
		}
		$this->dsrc->apply_conds($conds);
	}

	/* set order */
	public function set_order($col, $crit) {
		$this->order = array($col => $crit);
	}

	/* set fields (columns) */
	public function set_fields($fields, $callback = array()) {
		$this->fields = $fields;
		$this->fields_callback = $callback;
	}

	/* gen filter list */
	public function gen_filters() {
		$struct  = $this->dsrc->get_struct();
		$data    = $this->dsrc->get_rdata();

		$filters = array();

		/* consider filters */
		foreach($this->filters as $col => $conf) {
			if($struct[$col]) {
				$filter = array(
					'type'  => $conf['type'],
					'label' => $conf['label'],
				);

				/* select filter */
				if($conf['type'] == WF_CORE_DATASET_SELECT_FILTER) {
					/* get uniq list values */
					$options = array();
					foreach($data as $row) {
						$value = $row[$col];

						/* consider callback */
						$pvalue = $value;
						if($conf['callback']) {
							$pvalue = call_user_func($conf['callback'], $value);
						}

						$options[$value] = $pvalue;
					}
					$filter['options'] = $options;
				}
				/* slide filter */
				else if($conf['type'] == WF_CORE_DATASET_SLIDE_FILTER) {
					/* get min and max values */
					$min =  PHP_INT_MAX;
					$max = -PHP_INT_MAX;
					foreach($data as $row) {
						if($row[$col] < $min) {
							$min = $row[$col];
						}
						if($row[$col] > $max) {
							$max = $row[$col];
						}
					}
					$filter['min'] = $min;
					$filter['max'] = $max;
				}

				$filters[$col] = $filter;
			}
		}

		return($filters);
	}

	/* gen data list */
	public function gen_data() {
		/* retrieve data from datasource */
		$struct   = $this->dsrc->get_struct();
		$in_data  = $this->dsrc->get_data();
		$out_data = array();

		/* consider order */
		if($this->order) {
			uasort($in_data, array($this, 'callback_sort_data'));
		}

		/* consider fields */
		foreach($in_data as $row) {
			/* consider fields callback */
			if($this->fields_callback) {
				$row = call_user_func($this->fields_callback, $row);
			}

			/* generate data */
			$field = array();
			foreach($this->fields as $col => $conf) {
				$field[] = $row[$col];
			}
			$out_data[] = $field;
		}

		return($out_data);
	}

	/* get fields */
	public function get_fields() {
		return($this->fields);
	}

	/* get form filter */
	public function get_form_filter() {
		return($this->form_filter);
	}

	/* data sorting callback */
	public function callback_sort_data($a, $b) {
		$key  = key($this->order);
		$crit = current($this->order);

		if($a[$key] == $b[$key]) {
			return(0);
		}

		if($a[$key] < $b[$key]) {
			return(($crit == WF_ASC) ? -1 : 1);
		}
		else {
			return(($crit == WF_ASC) ? 1 : -1);
		}
	}
}
