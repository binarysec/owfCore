<?php

class wfr_core_admin_system_smtp extends wf_route_request {
	
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
		
		$this->a_core_html->set_meta_name(
			"viewport", 
			array(
				"content" => "width=device-width, initial-scale=1"
			)
		);
		
	}

	public function show() {
		
		$dsrc  = new core_datasource_db($this->wf, "core_smtp");
		$dset  = new core_dataset($this->wf, $dsrc);
		
		$filters = array();
		$cols = array(
			'description' => array(
				'name'      => $this->a_core_smtp->lang->ts('Description'),
				'orderable' => true,
			),
			'server_ip' => array(
				'name'      => $this->a_core_smtp->lang->ts('Server IP'),
				'orderable' => true,
			),
			'server_port' => array(
				'name'      => $this->a_core_smtp->lang->ts('Server Port'),
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

		$add_link = $this->a_core_smtp->dao->add_link();
		
		$in = array(
			"dao_link_add" => $add_link,
			"dataset" => $dview->render(NULL, $tplset)
		);	 
		$tpl->set_vars($in);

		/* Add back button */
		$this->a_admin_html->set_backlink($this->wf->linker('/admin/system'), "Home", "home");
		
		$this->a_admin_html->rendering($tpl->fetch('admin/system/smtp'));
		exit(0);

	}

	public function callback_row($row, $datum) {
		$add = htmlspecialchars($datum['server_ip']).':'.htmlspecialchars($datum['server_port']);
		
		$link = $this->a_core_smtp->dao->mod_link($datum['id']);
		$r = '<li><a href="'.$link.'">'.
				'<h3>'.htmlspecialchars($datum['description']).'</h3>'.
				'<p>Service address: '.$add.'</strong></p>'.
			'</a></li>';
		return($r);
		
		return("null");
		return(array(
			'description' => htmlspecialchars($datum['description']),
			'server_ip' => htmlspecialchars($datum['server_ip']),
			'server_port' => htmlspecialchars($datum['server_port']),
			'actions' => $action
		));
	}
		
}
