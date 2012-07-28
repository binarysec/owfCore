<?php

class wfr_core_dao extends wf_route_request {

	public $wf;
	private $waf_site;
	private $lang;
	private $error = array();
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * constructor
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function __construct($wf) {
		$this->wf = $wf;

		$this->a_core_dao = $this->wf->core_dao();
		$this->a_core_request = $this->wf->core_request();
		$this->session = $this->wf->session();
		$this->lang = $this->wf->core_lang()->get_context(
			"waf/dao"
		);
		$this->cipher = $this->wf->core_cipher();
	}
	
	private function selector() {
		/* load aggregator */
		$agg = $this->wf->load_agg($this->agg);
		$dao = $this->a_core_dao->get();
		
		/* check if the aggregator exists */
		if(array_key_exists($this->oid, $dao)) {
			if(is_object($dao[$this->oid]))
				return($dao[$this->oid]);
		}
		return(TRUE);
	}
	
	public function dialog() {
		$type = $this->wf->get_var("type");
		
		
		$back = $this->wf->get_var("back");
		
		$title = $this->wf->get_var("title");
		$body = $this->wf->get_var("body");
		
		
	// confirmation
	// erreur
// 		type = confirm/error
		
		$tpl = new core_tpl($this->wf);
		$in = array(
			"title" => $title,
			"body" => $body,
		);	 
		$tpl->set_vars($in);
		$this->wf->admin_html()->set_title(htmlentities($title));
		$this->wf->admin_html()->rendering($tpl->fetch('bsf/waf/dao/dialog'));
		exit(0);
	}
	
	
	public function show() {
		$this->agg = $this->a_core_request->get_argv(0);
		$this->oid =  $this->wf->get_var("oid");
		$this->uid = $this->wf->get_var("uid");
		$this->back = $this->cipher->get_var("back");
// 		$this->title = $this->cipher->get_var("title");
		$this->action = $this->wf->get_var("action");
		$this->type_mod = false;
		$this->fake = array();
		
		/* select form */
		$item = $this->selector();
		if(!is_object($item)) {
			$this->wf->display_error(
				404,
				"Data access object not found"
			);
			exit(0);
		}
	
		/* check form permission */
		if(!array_key_exists("perm", $item->struct["form"])) {
			$this->wf->display_error(
				403,
				"Data access object forbidden"
			);
			exit(0);
		}
		
		$ret = $this->session->check_permission($item->struct["form"]["perm"]);
		if(!$ret || !isset($item->struct["form"]["perm"])) {
			$this->wf->display_error(
				403,
				"Data access object forbidden"
			);
			exit(0);
		}
		
		$title = '';
		$body = '';
		
		if($this->uid > 0) {
			$ret = $item->get(array("id" => (int)$this->uid));
			if(!array_key_exists(0, $ret))
				exit(0);
				
			/* process update */
			if($this->action == 'process') {
				$r = $this->add_post($item, $this->uid);
				if(is_array($r))
					$this->error = $r;
// 				else {
// 					header("Location: ".$this->back);
// 					exit(0);
// 				}
				$ret = $item->get(array("id" => $this->uid));
			}
		
			$elements = $this->a_core_dao->draw_form($item, $ret[0], false);
			$this->type_mod = true;
			
			if(array_key_exists("mod_title", $item->struct["form"])) 
				$title = $item->struct["form"]["mod_title"];
			
			if(array_key_exists("mod_body", $item->struct["form"])) 
				$body = $item->struct["form"]["mod_body"];
				
		}
		else {
			
			
			/* process addition */
			if($this->action == 'process') {
				$r = $this->add_post($item);
				if(is_array($r))
					$this->error = $r;
	
			}
			
			$elements = $this->a_core_dao->draw_form($item, $this->fake, false);
			
			if(array_key_exists("add_title", $item->struct["form"])) 
				$title = $item->struct["form"]["add_title"];
			
			if(array_key_exists("add_body", $item->struct["form"])) 
				$body = $item->struct["form"]["add_body"];
		}
		
		$forms = $this->form_rendering($elements);
		
		
		$tpl = new core_tpl($this->wf);
		$in = array(
			"title" => $title,
			"body" => $body,
			"error" => $this->error,
			"forms" => $forms,
			"back" => $this->back,
			"rand" => rand()
		);	 
		$tpl->set_vars($in);
		$this->wf->admin_html()->set_title($this->lang->ts("Data access object"));
		$this->wf->admin_html()->rendering($tpl->fetch('core/dao/index'));
		exit(0);
	}
	
	public function process() {
	
// 		var_dump($_POST);
// 		exit(0);

// public function add_post($item, $id=NULL, $json=false) {
	}
	

	public function form_rendering($elements) {
		$forms = '<form action="?" method="post" class="ui-body ui-body-a ui-corner-all">';
		
		$forms .= '<input type="hidden" name="oid" value="'.$this->oid.'"/>';
		$forms .= '<input type="hidden" name="back" value="'.$this->cipher->encode($this->back).'"/>';
// 		$forms .= '<input type="hidden" name="title" value="'.$this->cipher->encode($this->title).'"/>';
		$forms .= '<input type="hidden" name="action" value="process"/>';
		
		if($this->uid > 0) 
			$forms .= '<input type="hidden" name="uid" value="'.$this->uid.'"/>';
			
		foreach($elements as $k => $v) {
			/* INPUT */ 
			if($v["kind"] == 1 || $v["kind"] == 3) {
				$insert = '<input type="text"'.
					' name="'.$k.'"' .
					' id="'.$k.'"' .
					' ';
				
				if($v["kind"] == 3)
					$insert .= ' readonly="readonly"';
				
				if(array_key_exists("value", $v))
					$insert .= ' value="'.$v["value"].'"';
				else
					$insert .= ' value=""';
					
				if(array_key_exists("size",$v))
					$insert .= ' size="'.$v["size"].'"';
					
				$insert .= ' />';

				$forms .= 
					'<div data-role="fieldcontain">' .
					'<label for="'.$k.'">' .
					$v["text"].' : ' .
					'</label>'.
					$insert .
					'</div>'."\n";
				;
			}
			
			/* SELECT */
			else if($v["kind"] == 6) {
				$select = '';
				if(!array_key_exists("value", $v))
					$v["value"] = '';

				/* read list */
				$select .= '<select name="'.$k.'" id="'.$k.'">';
				foreach($v["list"] as $lkey => $lval) {
				
					if($v["value"] == $lkey) 
						$select .= '<option value="'.$lkey.'" selected>'.$lval.'</option>';
					else
						$select .= '<option value="'.$lkey.'">'.$lval.'</option>';
				}
				$select .= '</select>';
				
				$forms .= 
					'<div data-role="fieldcontain">' .
					'<label for="'.$k.'">' .
					$v["text"].' : ' .
					'</label>'.
					$select .
					'</div>'."\n";
			}

		}
		
		$forms .= '<button type="submit" data-theme="b">Submit</button>';

		$sup_text = $this->lang->ts("Delete");
		if($this->uid > 0) 
			$forms .= '<a href="#" data-theme="f" data-role="button">'.$sup_text.'</a>';
			
		$forms .= "</form>";
		
		return($forms);
	}
	
	public function add_post($item, $id=NULL, $json=false) {
		$insert = array();
		$error = array(
			"msgs" => array()
		);
		
		/* read variable */
		foreach($item->data as $key => $val) {
			/* check permission */
			$ret = isset($val["perm"]) ? $this->session->check_permission($val["perm"]) : true;
			if($ret && isset($val["perm"]) && isset($val["kind"]) && $val["kind"] == OWF_DAO_RADIO) {
				$var = $this->wf->get_var($key);
				$insert[$key] = $var;
			}
			else if($ret && isset($val["perm"])) {
				if(isset($val["kind"])) {
					/* get var */
					$var = $this->wf->get_var($key);
					
					/* execute filter */
					if(isset($val["filter_cb"])) {
						$ret = call_user_func($val["filter_cb"], $item, &$var);
						if(is_string($ret))
							$error["msgs"][$key] = $ret;
						else if($ret && $var) {
							if(isset($val["return_cb"])){
								$ret2 = call_user_func($val["return_cb"], $item, &$var);
								if($ret2)
									$insert[$key] = $ret2;
								else
									$error["msgs"][$key] = $ret2;
							}
							else 
								$insert[$key] = $var;
						}
					}
					else if($var)
						$insert[$key] = $var;
				}
				
			}
		}
		
		$this->fake = $insert;
		
		if(count($insert) > 0 && count($error["msgs"]) == 0) {
			if(isset($id))
				$item->modify(array("id" => $id), $insert);
			else
				$this->uid = $item->add($insert);
			return(true);
		}
		else if(count($error["msgs"]) == 0)
			$error["__dao_title"] = "No data to insert";
		
		if($json) {
			echo json_encode($error);
			return($error);
		}
		return($error);
	}
	
	public function get($item) {
		$_where = $this->wf->get_var("where");
// 		$_order = $this->wf->get_var("order");
		$where = array();
// 		$order = array();

		/* read variable */
		if(is_array($_where)) {
			foreach($item->data as $key => $val) {
				/* check permission */
				$ret = $this->session->check_permission($val["perm"]);
				if($ret) {
					$var = &$_where[$key];
					if(isset($var))
						$where[$key] = $var;
				}	
			}
		}
		
		$res = $item->get($where);		
		echo json_encode($res);		
		exit(0);
	}

}
