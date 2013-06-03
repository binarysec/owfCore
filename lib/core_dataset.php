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

define('WF_CORE_DATASET_SELECT',   1);
define('WF_CORE_DATASET_SLIDE',    2);
define('WF_CORE_DATASET_ACTIVATE', 3);

define('WF_CORE_DATASET_SELECT_BAR_ALL',		1);
define('WF_CORE_DATASET_SELECT_BAR_ONLY_PAGE',	2);
define('WF_CORE_DATASET_SELECT_BAR_NONE',		3);

class core_dataset {

	private $fw   = null;
	private $dsrc = null;

	private $cols    = array();
	private $conds   = array();
	private $order   = array();
	private $filters = array();
	private $row_callback   = null;
	private $rows_per_page  = null;
	private $page_nb        = 1;
	private $display_select_bar = WF_CORE_DATASET_SELECT_BAR_ALL;
	private $range_rows_per_page = array(25,50,100);
	
	public function __construct($wf, $dsrc) {
		$this->wf   = $wf;
		$this->dsrc = $dsrc;

		/* convert datasource struct to columns format */
		$struct = $this->dsrc->get_struct();
		foreach($struct as $name => $type) {
			$this->cols[$name] = array(
				'name' => $name
			);
		}
	}

	public function get_search() {
		return(htmlentities($this->wf->get_var($this->dsrc->get_name().'_search')));
	}
	
	public function auto_search() {
		$search = $this->wf->get_var($this->dsrc->get_name().'_search');
		if(strlen($search) > 0) {
			$sc = array();
			foreach($this->cols as $col => $way) {
				if(array_key_exists('search', $way) && $way['search'] == true)
					$sc[] = $col;
			}
			$this->dsrc->set_search($sc, $search);
		}
	}
	
	public function set_display_select_bar($display) {
		if($display >= WF_CORE_DATASET_SELECT_BAR_ALL &&
		$display <= WF_CORE_DATASET_SELECT_BAR_NONE)
			$this->display_select_bar = $display;
	}

	public function get_display_select_bar() {
		return $this->display_select_bar;
	}

	public function auto_order() {
		$order = $this->wf->get_var($this->dsrc->get_name().'_order');
		if(!is_array($order) || !$order) {
			return;
		}
		$orders = array();
		foreach($order as $col => $way) {
			if($way) {
				$this->set_order(array($col => ($way == 'D') ? WF_DESC : WF_ASC));
			}
		}
	}

	public function set_order($order) {
		$this->order = array();
		$struct = $this->dsrc->get_struct();
		foreach($order as $col => $way) {
// 			if(isset($this->cols[$col]['orderable']) && $this->cols[$col]['orderable']) {
				$this->order[$col] = $way;
// 			}
		
		}
		
	}

	public function set_conds($conds) {
		$pconds = array();
		if(is_array($this->filters)) {
			foreach($this->filters as $col => $conf) {
				if(isset($conds[$col])) {
					if($conf['type'] == WF_CORE_DATASET_SELECT && trim($conds[$col])) {
						$pconds[] = array($col, '~=', $conds[$col]);
					}
				}
			}
		}
		$this->conds = $pconds;
	}
	
	public function auto_conds() {
		/* retrieve conds */
		$conds = $this->wf->get_var($this->dsrc->get_name().'_filter');
		/* compute datasource conds */
		$this->set_conds($conds);
	}

	public function set_rows_per_page($nb) {
		$this->rows_per_page = max(0, $nb);
	}

	public function set_range_rows_per_page($ar) {
		$this->range_rows_per_page = $ar;
	}

	public function set_page_nb($nb) {
		$this->page_nb = max(1, $nb);
	}

	public function auto_page_nb() {
		/* retrieve page number */
		$this->set_page_nb(intval($this->wf->get_var($this->dsrc->get_name().'_page')));
	}

	public function auto_rows_per_page() {
		/* retrieve number of rows per page */
		$p = intval($this->wf->get_var($this->dsrc->get_name().'_rows_per_page'));
		
		if(in_array($p,$this->range_rows_per_page))
			$this->set_rows_per_page($p);
		else {
			if(!$this->rows_per_page)
				$this->set_rows_per_page($this->range_rows_per_page[0]);
		}
			
	}

	public function set_cols($cols) {
		// Sanatize ...
		foreach($cols as $k => $col) {
			if(!isset($col["name"]))
				$cols[$k]['name'] = '';
			if(!isset($col["orderable"]))
				$cols[$k]['orderable'] = false;
		}
		$this->cols = $cols;
	}

	public function set_row_callback($row_callback) {
		$this->row_callback = $row_callback;
	}

	public function set_filters($filters) {
		$this->filters = $filters;
	}

	public function get_cols() {
		return($this->cols);
	}

	public function get_rows_per_page() {
		return intval($this->rows_per_page);
	}
	
	public function get_range_rows_per_page() {
		return($this->range_rows_per_page);
	}
	
	public function get_page_nb() {
		return intval($this->page_nb);
	}

	public function get_filters() {
		$filters = array();
		$struct  = $this->dsrc->get_struct();

		/* consider filters */
		if(is_array($this->filters)) {
			foreach($this->filters as $col => $conf) {
				if(isset($struct[$col])) {
					$filter = array(
						'type'  => $conf['type'],
						'label' => $conf['label'],
					);

					/* select filter */
					if(isset($conf['type']) && $conf['type'] = WF_CORE_DATASET_SELECT) {
						/* get uniq list values */
						$filter['options'] = array();
						$options = $this->dsrc->get_options($col);
						
						/* remove filter is there is only one option available */
						if(count($options) < 2)
							continue;
						
						foreach($options as $option) {
							$value  = $option[$col];
							$pvalue = $value;

							/* consider callback */
							if(isset($conf['callback'])) {
								$pvalue = call_user_func($conf['callback'], $value);
								
								if(!$pvalue)
									continue;
							}

							$filter['options'][$value] = $pvalue;
						}
					}

					$filters[$col] = $filter;
				}
			}
		}

		return($filters);
	}

	public function get_rows() {
		/* retrieve rows from datasource considering all options */
		$rows = array();

		/* apply auto conditions, page number, number of rows per page and ordering */
		$this->auto_conds();
		$this->auto_search();
		$this->auto_page_nb();
		$this->auto_rows_per_page();
		$this->auto_order();

		/* number of page should not exceed total num rows */
		if($this->rows_per_page) {
			$this->set_page_nb(min(
				ceil($this->get_total_num_rows() / $this->rows_per_page),
				$this->page_nb
			));
		}

		$data = $this->dsrc->get_data(
			$this->conds,
			$this->order,
			($this->page_nb - 1) * $this->rows_per_page,
			$this->rows_per_page
		);

		foreach($data as $datum) {
			$row = array();
			foreach($this->cols as $col => $conf) {
				$row[$col] = isset($datum[$col]) ? $datum[$col] : NULL;
			}

			/* consider row callback */
			if($this->row_callback) {
				$row = call_user_func($this->row_callback, $row, $datum);
			}
			$rows[] = $row;
		}

		return($rows);
	}

	public function get_name() {
		return($this->dsrc->get_name());
	}

	public function get_total_num_rows($ignore_conds = false, $ignore_preconds = false) {
		return $this->dsrc->get_num_rows(
			$ignore_conds ? array() : $this->conds,
			$ignore_preconds
		);
	}
	
}
