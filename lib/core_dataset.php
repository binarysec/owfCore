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
			if($struct[$col] && $this->cols[$col]['orderable'] == true) {
				$this->order[$col] = $way;
			}
		}
	}

	public function set_conds($conds) {
		$pconds = array();
		foreach($this->filters as $col => $conf) {
			if($conds[$col]) {
				if($conf['type'] == WF_CORE_DATASET_SELECT) {
					$pconds[] = array($col, '~=', $conds[$col]);
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

	public function set_page_nb($nb) {
		$this->page_nb = max(1, $nb);
	}

	public function auto_page_nb() {
		/* retrieve page number */
		$this->set_page_nb($this->wf->get_var($this->dsrc->get_name().'_page'));
	}

	public function auto_rows_per_page() {
		/* retrieve number of rows per page */
		$this->set_rows_per_page($this->wf->get_var($this->dsrc->get_name().'_rows_per_page'));
	}

	public function set_cols($cols) {
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
		return($this->rows_per_page);
	}

	public function get_page_nb() {
		return($this->page_nb);
	}

	public function get_filters() {
		$filters = array();
		$struct  = $this->dsrc->get_struct();

		/* consider filters */
		foreach($this->filters as $col => $conf) {
			if($struct[$col]) {
				$filter = array(
					'type'  => $conf['type'],
					'label' => $conf['label'],
				);

				/* select filter */
				if($conf['type'] = WF_CORE_DATASET_SELECT) {
					/* get uniq list values */
					$filter['options'] = array();
					$options = $this->dsrc->get_options($col);
					foreach($options as $option) {
						$value  = $option[$col];
						$pvalue = $value;

						/* consider callback */
						if($conf['callback']) {
							$pvalue = call_user_func($conf['callback'], $value);
						}

						$filter['options'][$value] = $pvalue;
					}
				}

				$filters[$col] = $filter;
			}
		}

		return($filters);
	}

	public function get_rows() {
		/* retrieve rows from datasource considering all options */
		$rows = array();

		/* apply auto conditions, page number, number of rows per page and ordering */
		$this->auto_conds();
		$this->auto_page_nb();
		$this->auto_rows_per_page();
		$this->auto_order();

		/* number of page should not exceed total num rows */
		if($this->rows_per_page) {
			$this->page_nb = min(
				ceil($this->get_total_num_rows() / $this->rows_per_page),
				$this->page_nb
			);
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
				$row[$col] = $datum[$col];
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

	public function get_total_num_rows() {
		return($this->dsrc->get_num_rows($this->conds));
	}

}