<?php

class wfr_core_dao extends wf_route_request {
	private $a_session;
	private $a_core_dao;
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * constructor
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function __construct($wf) {
		$this->wf = $wf;
		$this->a_core_request = $this->wf->core_request();
		
		$this->a_session = $this->wf->session();
		$this->a_core_dao = $this->wf->core_dao();
	}

	private function selector() {
		/* get the aggregator */
		$aggregator = $this->wf->get_var("agg");
		$id = (int)$this->wf->get_var("id");
		
		/* load aggregator */
		$agg = $this->wf->load_agg($aggregator);
		$dao = $this->a_core_dao->get();
		
		/* check if the aggregator exists */
		if(array_key_exists($id, $dao)) {
			if(is_object($dao[$id]))
				return($dao[$id]);
		}
		return(TRUE);
	}
	
	private function draw_form($item) {
		echo "<table>\n";
		/* follow and build form */
		foreach($item->data as $key => $val) {	
			if($val["type"] == OWF_DAO_INPUT) {
				echo 
					"<tr>\n".
					'<td>'.$val["name"].': </td>'.
					'<td><input type="text"></td>'.
					"</tr>\n";
			}
			else if($val["kind"] == OWF_DAO_SELECT) {
				if(isset($val["select_cb"]))
					$list = call_user_func($val["select_cb"], $item, $val);
	
				echo 
					"<tr>\n".
					"<td>$val[name]: </td>".
					'<td><select>';
					
				foreach($list as $id => $entry) {
					echo "<option value=\"$id\">".
						$entry.
						"</option>\n";
				}
				
				echo '</select></td>'.
					"</tr>\n";
			}
		}
		echo "</table>\n";
	}
	
	public function form() {
		$mode = $this->wf->get_var("mode");
		$item = $this->selector();
		if(!is_object($item)) {
			$this->wf->display_error(
				404,
				"Data access object not found"
			);
			exit(0);
		}

		if($mode == 'add') {
			$this->draw_form($item);
		}
		
	}

	
}
