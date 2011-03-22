<?php

class wfr_core_smtp extends wf_route_request {
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * constructor
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function __construct($wf) {
		$this->wf = $wf;

		$this->a_core_html = $this->wf->core_html();
		$this->a_admin_html = $this->wf->admin_html();
		$this->a_core_smtp = $this->wf->core_smtp();
		
		$this->a_session = $this->wf->session();
	}

	public function page() {
		
		$dsrc  = new core_datasource_db($this->wf, "core_smtp");
		$dset  = new core_dataset($this->wf, $dsrc);
		
		$filters = array();
		$cols = array(
			'description' => array(
				'name'      => 'Description',
				'orderable' => true,
			),
			'server_ip' => array(
				'name'      => 'Server IP',
				'orderable' => true,
			),
			'server_port' => array(
				'name'      => 'Server Port',
				'orderable' => true,
			),
			'actions' => array()
		);
		
		$dset->set_cols($cols);
		$dset->set_filters($filters);
		
		$dset->set_row_callback(array($this, 'callback_row'));

		/* template utilisateur */
		$tplset = array();
		$dview = new core_dataview($this->wf, $dset);
		$tpl = new core_tpl($this->wf);

		$in = array(
			"button_add" => $this->a_core_smtp->dao->button_add("Add SMTP server"),
			"dataset" => $dview->render(NULL, $tplset)
		);	 
		$tpl->set_vars($in);

		$this->a_admin_html->rendering($tpl->fetch('core/smtp/list'));
		exit(0);

	}

	public function callback_row($row, $datum) {
		$action = $this->a_core_smtp->dao->button_remove("Delete", $datum['id']);
		
		return(array(
			'description' => htmlspecialchars($datum['description']),
			'server_ip' => htmlspecialchars($datum['server_ip']),
			'server_port' => htmlspecialchars($datum['server_port']),
			'actions' => $action
		));
	}
		
}
