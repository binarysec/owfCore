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
		$this->admin_html = $this->wf->admin_html();
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
		if(!is_object($item))
			$this->wf->display_error(404, "Data access object not found", true);
		
		/* check form permission */
		if(	(	!isset($item->struct["form"]["perm"]) ||
				!$this->session->check_permission($item->struct["form"]["perm"])
			) &&
			(	!isset($item->struct["form"]["perm_cb"]) ||
				!call_user_func($item->struct["form"]["perm_cb"], $item)
			)
				)
				$this->wf->display_error(403, "Data access object forbidden", true);
		
		if($item->capable == OWF_DAO_FORBIDDEN)
			$this->wf->display_error(403, "Data access object forbidden", true);
		
		$title = '';
		$body = '';
		
		if($this->uid > 0) {
			$ret = $item->get(array("id" => (int)$this->uid));
			if(!array_key_exists(0, $ret))
				$this->wf->display_error(500, "Parameter uid is missing", true);
				
			/* process update */
			if($this->action == 'process') {
				if(!($item->capable & OWF_DAO_EDIT))
					$this->wf->display_error(404, "DAO edit action forbidden", true);
				
				$r = $this->add_post($item, $this->uid);
				if(!is_array($r))
					$this->wf->redirector($this->back);
				$this->error = $r;
				$ret = $item->get(array("id" => $this->uid));
			}
			else if($this->action == 'del') {
				if(!($item->capable & OWF_DAO_REMOVE))
					$this->wf->display_error(404, "DAO remove action forbidden", true);
				
				$elements = $this->a_core_dao->draw_form($item, $this->fake, false);
				
				/* search of octopuses */
				foreach($elements as $k => $v) {
					if(isset($v["kind"]) && $v["kind"] == OWF_DAO_OCTOPUS) {
						foreach($item->childs as $child) {
							$key = isset($v["db-field"]) ? $v["db-field"] : "father_id";
							$this->wf->db->query(
								new core_db_delete(
									$item->name."_".$child->get_name(),
									array($key => $this->uid)
								)
							);
						}
					}
				}
				
				$ret = $item->remove(array("id" => $this->uid));
				$this->wf->redirector($this->back);
			}
			
			if(!($item->capable & OWF_DAO_EDIT))
				$this->wf->display_error(404, "DAO edit action forbidden", true);
			
			$elements = $this->a_core_dao->draw_form($item, $ret[0], false);
			$this->type_mod = true;
			
			if(array_key_exists("mod_title", $item->struct["form"])) 
				$title = $item->struct["form"]["mod_title"];
			
			if(array_key_exists("mod_body", $item->struct["form"])) 
				$body = $item->struct["form"]["mod_body"];
				
		}
		else {
			if(!($item->capable & OWF_DAO_ADD))
				$this->wf->display_error(404, "DAO add action forbidden", true);
			
			/* process addition */
			if($this->action == 'process') {
				$r = $this->add_post($item);
				if(!is_array($r))
					$this->wf->redirector($this->back);
				$this->error = $r;
			}
			
			$elements = $this->a_core_dao->draw_form($item, $this->fake, false);
			
			if(array_key_exists("add_title", $item->struct["form"])) 
				$title = $item->struct["form"]["add_title"];
			
			if(array_key_exists("add_body", $item->struct["form"])) 
				$body = $item->struct["form"]["add_body"];
		}
		
		/* tpl conf */
		$this->tpl = new core_tpl($this->wf);
		$in = array(
			"title" => $title,
			"body" => $body,
			"error" => $this->error,
			"back" => $this->back,
			"rand" => rand(),
		);
		$this->tpl->set_vars($in);
		
		/* render form */
		$this->maps = array();
		$forms = $this->form_rendering($elements, $item);
		$this->tpl->set("maps", $this->maps);
		$this->tpl->set("forms", $forms);
		
		/* render page */
		$this->admin_html->set_backlink($this->back);
		$this->admin_html->set_title($title);
		$this->admin_html->rendering($this->tpl->fetch('core/dao/index'));
		exit(0);
	}
	
	public function process() {
	
// 		var_dump($_POST);
// 		exit(0);

// public function add_post($item, $id=NULL, $json=false) {
	}
	

	public function form_rendering($elements, $item = null) {
		$forms = '<form action="?" method="post" class="ui-body ui-body-a ui-corner-all" style="width: 65%; margin: auto;">';
		
		$forms .= '<input type="hidden" name="oid" value="'.$this->oid.'"/>';
		$forms .= '<input type="hidden" name="back" value="'.$this->cipher->encode($this->back).'"/>';
// 		$forms .= '<input type="hidden" name="title" value="'.$this->cipher->encode($this->title).'"/>';
		$forms .= '<input type="hidden" name="action" value="process"/>';
		
		if($this->uid > 0) 
			$forms .= '<input type="hidden" name="uid" value="'.$this->uid.'"/>';
		
		foreach($elements as $k => $v)
			$forms .= $this->form_render_element($k, $v, $v["kind"], $item);
		
		$can_add = false;
		if(!is_null($item))
			$can_add = ($this->uid > 0) ? ($item->capable & OWF_DAO_EDIT) :
				($item->capable & OWF_DAO_ADD);
		$can_del = !is_null($item) && ($item->capable & OWF_DAO_REMOVE) == OWF_DAO_REMOVE && $this->uid > 0;
		$add_txt = "<button type='submit' data-theme='b'>Submit</button>";
		$del_txt =
			'<a href="#owf-core-dao-delete" data-rel="popup" data-position-to="window" data-role="button" data-inline="true" data-transition="pop" data-theme="f" style="width: 100%;">'.$this->lang->ts("Delete").'</a>'.
			'<div data-role="popup" id="owf-core-dao-delete" data-theme="f" class="ui-corner-all">'.
				'<a href="#" data-rel="back" data-role="button" data-theme="a" data-icon="delete" data-iconpos="notext" class="ui-btn-right">'.$this->lang->ts("Close").'</a>'.
				'<div data-role="header" data-theme="a" class="ui-corner-top"><h1>'.$this->lang->ts("Delete this object ?").'</h1></div>'.
				'<div data-role="content" data-theme="b" class="ui-corner-bottom ui-content">'.
					'<h3 class="ui-title">'.$this->lang->ts("Are you sure you want to delete this object ?").'</h3>'.
					'<p>'.$this->lang->ts("This action cannot be undone.").'</p>'.
					'<a href="#" data-role="button" data-inline="true" data-rel="back" style="width: 40%;">'.$this->lang->ts("Cancel").'</a>'.
					'<a href="'.$this->selector()->del_link($this->uid, TRUE).'" data-theme="f" data-role="button" data-inline="true" data-transition="flow" style="width: 40%;">'.$this->lang->ts("Delete").'</a>'.
				'</div>'.
			'</div>';
		
		if($can_add && $can_del)
			$forms .= "<fieldset class='ui-grid-a'><div class='ui-block-a'>$add_txt</div><div class='ui-block-b'>$del_txt</div></fieldset>";
		elseif($can_add)
			$forms .= $add_txt;
		elseif($can_del)
			$forms .= $del_txt;
		
		$forms .= "</form>";
		
		return($forms);
	}
	
	public function form_render_element($k, $v, $kind, $item, $parentkind = null, $parentdata = null) {
		
		$name = $k;
		if($parentkind == OWF_DAO_OCTOPUS)
			$name = "$parentdata[name]-$name";
		
		switch($kind) {
			case OWF_DAO_INPUT:
			case OWF_DAO_NUMBER:
			case OWF_DAO_INPUT_READON:
			case OWF_DAO_NUMBER_READON:
			case OWF_DAO_UPLOAD:
			case OWF_DAO_DATE:
			case OWF_DAO_DATE_READON:
				$value = isset($v["value"]) ? $v["value"] : "";
				$readonly =
					$v["kind"] == OWF_DAO_INPUT_READON ||
					$v["kind"] == OWF_DAO_NUMBER_READON ||
					$v["kind"] == OWF_DAO_DATE_READON
					? "disabled='disabled'" : "";
				$type = "text";
				
				if($v["kind"] == OWF_DAO_NUMBER || $v["kind"] == OWF_DAO_NUMBER_READON)
					$type = "number";
				/*elseif($v["kind"] == OWF_DAO_UPLOAD)
					$type = "file";*/
				
				$class = "";
				$octo_css = "";
				if($parentkind == OWF_DAO_OCTOPUS) {
					$class = "class='$parentdata[name] $parentdata[name]-$parentdata[id]'";
					if($parentdata["dft_val"] != $parentdata["id"])
						$octo_css = "style='display: none;'";
				}
				
				return	"<div data-role='fieldcontain' $class $octo_css>".
							"<label for='$name'>"."$v[text] : </label>".
							"<input type='$type' name='$name' id='$name' value='$value' $readonly />".
						"</div>\n";
			
			case OWF_DAO_HIDDEN:
				$value = isset($v["value"]) ? $v["value"] : "";
				return "<input type='hidden' name='$name' id='$name' value='$value' />\n";
			
			case OWF_DAO_SELECT:
			case OWF_DAO_OCTOPUS:
			case OWF_DAO_LINK_MANY_TO_ONE:
				if(!isset($v["value"]))
					$v["value"] = '';
				
				$octo_js = "";
				$dft_key = "";
				
				if($v["kind"] == OWF_DAO_OCTOPUS) {
					$octo_js = <<<EOT
onchange='\$(".$name").hide();\$(".$name-" + $(this).val()).show();''
EOT;
					$dft_key = key($v["list"]);
				}
				
				$select = "<select data-native-menu='false' name='$name' id='$name' $octo_js>";
				
				/* if select and no values, add an empty entry */
				if($kind != OWF_DAO_OCTOPUS && !isset($v["list"][$v["value"]]))
					$select .= "<option value=''>&nbsp;</option>";
				
				foreach($v["list"] as $lkey => $lval) {
					$selected = $v["value"] == $lkey ? "selected='selected'" : "";
					
					$select .= "<option value='$lkey' $selected>$lval</option>";
					if($v["kind"] == OWF_DAO_OCTOPUS && $selected)
						$dft_key = $lkey;
				}
				$select .= "</select>";
				
				if($v["kind"] == OWF_DAO_OCTOPUS) {
					$octopus = "";
					foreach($item->childs as $child) {
						
						$dft_val = "";
						if($this->uid > 0 && $child->get_id() == intval($v["value"])) {
							$key = isset($v["db-field"]) ? $v["db-field"] : "father_id";
							$q = new core_db_select(
								$item->name."_".$child->get_name(),
								null,
								array($key => $this->uid)
							);
							$this->wf->db->query($q);
							$dft_val = current($q->get_result());
						}
						
						foreach($child->get_struct() as $field => $info) {
							$info["value"] = is_array($dft_val) ? $dft_val[$field] : $dft_val;
							if(isset($info["cb_get"]))
								$info["value"] = call_user_func($info["cb_get"], $child, $info["value"]);
							$octopus .= $this->form_render_element($field, $info, $info["kind"], $item, $kind, 
								array(
									"name" => $name,
									"id" => $child->get_id(),
									"dft_val" => $dft_key
								)
							);
						}
					}
					$select .= $octopus;
				}
				
				return	"<div data-role='fieldcontain'>".
							"<label for='$name'>$v[text] : </label>".
								(($v["kind"] != OWF_DAO_LINK_MANY_TO_ONE || count($v["list"])) ?
									$select :
									"<input type='text' name='$name' id='$name' disabled=disabled value='".$this->lang->ts('Aucun élément existant')."' />").
						"</div>\n";
			
			case OWF_DAO_RADIO:
			case OWF_DAO_RADIO_READON:
				if(!isset($v["list"]))
					;// throw error
				
				$inputs = '';
				if(!isset($v["value"]))
					$v["value"] = key($v["list"]);
				$disabled = $v["kind"] == OWF_DAO_RADIO_READON ? "disabled='disabled'" : "";
				
				foreach($v["list"] as $key => $val) {
					$checked = $v["value"] == $key ? "checked='checked'" : "";
					$inputs .= "<input type='radio' name='$name' id='$name-$key' value='$key' $checked $disabled />";
					$inputs .= "<label for='$name-$key'>$val</label>";
				}
				
				return	"<div data-role='fieldcontain'>".
							"<fieldset data-role='controlgroup' data-type='horizontal'>".
								"<legend>$v[text] : </legend>".
								$inputs.
							"</fieldset>".
						"</div>\n";
			
			case OWF_DAO_CHECKBOX:
			case OWF_DAO_CHECKBOX_READON:
			case OWF_DAO_LINK_MANY_TO_MANY:
					if(!isset($v["list"]))
						;// throw error
					$inputs = '';
					$v["value"] = isset($v["value"]) ? explode(",", $v["value"]) : array();
					$disabled = $v["kind"] == OWF_DAO_CHECKBOX_READON ? "disabled='disabled'" : "";
					
					foreach($v["list"] as $key => $val) {
						$checked = in_array($key, $v["value"]) ? "checked='checked'" : "";
						$inputs .= "<input type='checkbox' name='".$name."[]' id='$name-$key' value='$key' $checked $disabled />";
						$inputs .= "<label for='$name-$key'>$val</label>";
					}
					
					return	"<div data-role='fieldcontain'>".
								"<fieldset data-role='controlgroup' data-type='horizontal'>".
									"<legend>$v[text] : </legend>".
									(($v["kind"] == OWF_DAO_LINK_MANY_TO_MANY && empty($v["list"])) ?
										"<input type='text' name='$name' id='$name' disabled=disabled value='".$this->lang->ts('Aucun élément existant')."' />" :
										$inputs).
								"</fieldset>".
							"</div>\n";
			
			case OWF_DAO_FLIP:
				$texton = isset($v["texton"]) ? $v["texton"] : $this->lang->ts("On");
				$textoff = isset($v["textoff"]) ? $v["textoff"] : $this->lang->ts("Off");
				$value = isset($v["value"]) ? (bool) $v["value"] : false;
				$on = $value ? "selected='selected'" : "";
				
				return	"<div data-role='fieldcontain'>".
							"<label for='$name'>$v[text] : </label>".
							"<select name='$name' id='$name' data-role='slider'>".
								"<option value='0'>$textoff</option>".
								"<option value='1' $on>$texton</option>".
							"</select>".
						"</div>\n";
			
			case OWF_DAO_SLIDER:
				$value = isset($v["value"]) ? (int) $v["value"] : 0;
				$min = isset($v["min"]) ? (int) $v["min"] : 0;
				$max = isset($v["max"]) ? (int) $v["max"] : 100;
				$step = isset($v["step"]) ? (int) $v["step"] : 1;
				if($min == $max)
					$max++;
				elseif($min > $max)
					list($max, $min) = array($min, $max);
				$value = max(min($value, $max), $min);
				
				return	"<div data-role='fieldcontain'>".
							"<label for='$name'>$v[text] : </label>".
							"<input type='range' name='$name' id='$name' value='$value' min='$min' max='$max' step='$step' data-highlight='true' />\n".
						"</div>\n";
			
			case OWF_DAO_MAP:
				$longitude = isset($v["value_longitude"]) ? floatval($v["value_longitude"]) : 0;
				$latitude = isset($v["value_latitude"]) ? floatval($v["value_latitude"]) : 0;
				
				$this->maps[$name] = array(
					"latitude" => $latitude,
					"longitude" => $longitude,
					"text" => $v["text"],
				);
				
				return	"<div data-role='fieldcontain'>".
							"<label for='$name' class='ui-select'>$v[text] :</label>".
							"<a id='$name' style='width: 75%;' data-rel='popup' data-role='button' data-theme='a' data-inline='true' href='#owf-dao-map-popup-$name'>".
								"Changer les coordonnées".
							"</a>".
							"<input type='hidden' id='owf-dao-map-form-data-$name-latitude' name='".$name."_latitude' value='$latitude' />".
							"<input type='hidden' id='owf-dao-map-form-data-$name-longitude' name='".$name."_longitude' value='$longitude' />".
						"</div>\n";
				
			case OWF_DAO_TEXT:
				$value = isset($v["value"]) ? $v["value"] : "";
				$insert = "<textarea name='$name' id='$name'/>$value</textarea>";
				return	"<div data-role='fieldcontain'>".
							"<label for='$name'>"."$v[text] : </label>".
							$insert.
						"</div>\n";
			
			default:
				return "";
		}
	}
	
	public function add_post($item, $id=NULL, $json=false) {
		$insert = array();
		$delayed_query = array();
		$error = array(
			"msgs" => array()
		);
		
		/* read variable */
		foreach($item->data as $key => $val) {
			
			/* check permission and required parameters */
			$ret = false;
			if(isset($val["perm"], $val["kind"])) {
				$ret = $this->session->check_permission($val["perm"]);
			}
			elseif(isset($val["perm_cb"], $val["kind"])) {
				$ret = call_user_func($val["perm_cb"], $item, $val);
			}
			else {
				//$this->wf->display_error(404, "Missing parameters", true);
			}
			
			if($ret) {
				/* get var */
				$var = $this->wf->get_var($key);
				
				/* sanatize */
				if($val["kind"] == OWF_DAO_SELECT) {
					if(strlen($var) > 0) {
						/* check if in list */
						if(!isset($val["list"][$var])) {
							$error["msgs"][$key] = $this->lang->ts("Value")." \"".htmlspecialchars($var)."\" ".
								$this->lang->ts("is not available for $val[name]");
						}
					}
					elseif(!isset($val["allow-empty"]) || !$val["allow-empty"])
						$error["msgs"][$key] = $this->lang->ts("You did not fill the field ").$val["name"];
				}
				
				if($val["kind"] == OWF_DAO_NUMBER)
					$var = floatval($var);
				
				if($val["kind"] == OWF_DAO_LINK_MANY_TO_ONE) {
					if(strlen($var) > 0) {
						/* check if in list */
						$ret = call_user_func($val["dao"], $val["field-id"], $var);
						
						if(empty($ret)) {
							$error["msgs"][$key] = $this->lang->ts("Value")." \"".htmlspecialchars($var)."\" ".
								$this->lang->ts("is not available for ").$val["name"];
						}
					}
					elseif(!isset($val["allow-empty"]) || !$val["allow-empty"])
						$error["msgs"][$key] = $this->lang->ts("You did not fill the field ").$val["name"];
				}
				
				/* store variable */
				if($val["kind"] == OWF_DAO_RADIO) {
					$insert[$key] = $var;
				}
				elseif($val["kind"] == OWF_DAO_CHECKBOX) {
					$insert[$key] = is_array($var) ? implode(",", $var) : "";
				}
				elseif($val["kind"] == OWF_DAO_FLIP) {
					$insert[$key] = (bool) intval($var);
				}
				elseif($val["kind"] == OWF_DAO_DATE && $val["type"] == WF_INT) {
					$date = explode("/", $var);
					
					if(	count($date) == 3 &&
						strlen($date[0]) == 2 &&
						strlen($date[1]) == 2 &&
						strlen($date[2]) == 2
						) {
							$insert[$key] = mktime(0, 0, 0, $date[1], $date[0], $date[2]);
					}
					else
						$error["msgs"][$key] = $this->lang->ts("Malformed date ").htmlspecialchars($var).$this->lang->ts(" for field ")."\"$val[name]\"";
				}
				elseif($val["kind"] == OWF_DAO_MAP) {
					$lat = floatval($this->wf->get_var($key."_latitude"));
					$lon = floatval($this->wf->get_var($key."_longitude"));
					
					/* sanatize insane values */
					$insert[$key."_latitude"] = fmod($lat, 85.);
					$insert[$key."_longitude"] = fmod($lon, 180.);
				}
				elseif($val["kind"] == OWF_DAO_LINK_MANY_TO_MANY) {
					$delayed_query[] = array(
						"link" => $val["link"],
						"var" => $var
					);
				}
				elseif(	$val["kind"] ==	OWF_DAO_INPUT_READON ||
						$val["kind"] ==	OWF_DAO_NUMBER_READON ||
						$val["kind"] ==	OWF_DAO_RADIO_READON ||
						$val["kind"] ==	OWF_DAO_CHECKBOX_READON ||
						$val["kind"] ==	OWF_DAO_DATE_READON) {
				}
				elseif($val["kind"] == OWF_DAO_OCTOPUS && isset($item->childs[$var])) {
					
					$insert[$key] = intval($var);
					$key = isset($val["db-field"]) ? $val["db-field"] : "father_id";
					
					$octo_insert = array();
					foreach($item->childs[$var]->get_struct() as $k => $v) {
						$octo_insert[$k] = $this->wf->get_var(strtolower($val["name"])."-$k");
						if(isset($v["cb_check"])) {
							$ret = call_user_func(
								$v["cb_check"],
								$item->childs[$var],
								$octo_insert[$k]
							);
							if(is_string($ret))
								$error["msgs"][] = $ret;
						}
						if(isset($v["cb_add"]))
							$octo_insert[$k] = call_user_func(
								$v["cb_add"],
								$item->childs[$var],
								$octo_insert[$k]
							);
					}
					
				}
				else {
					/* execute filter */
					if(isset($val["filter_cb"])) {
						$ret = call_user_func($val["filter_cb"], $item, $var, $id);
						if(is_string($ret))
							$error["msgs"][$key] = $ret;
					}
					
					if(empty($error["msgs"][$key])) {
						if(isset($val["return_cb"])) {
							$ret2 = call_user_func($val["return_cb"], $item, $var);
							if($ret2)
								$insert[$key] = $ret2;
							else
								$error["msgs"][$key] = $ret2;
						}
						else 
							$insert[$key] = $var;
					}
				}
			}
			else {
				//$this->wf->display_error(403, "Adding DAO forbidden", true);
			}
		}
		
		$this->fake = $insert;
		
		if(count($insert) > 0 && count($error["msgs"]) == 0) {
			
			$add = !isset($id);
			if(!$add) {
				if(isset($val["kind"]) && $val["kind"] == OWF_DAO_OCTOPUS && isset($item->childs[$var])) {
					$current = current($item->get(array("id" => $id)));
					$type_old = intval($current["type"]);
					$type_new = intval($insert["type"]);
					$type_changed = $type_old != $type_new;
				}
				$item->modify(array("id" => $id), $insert);
			}
			else
				$id = $this->uid = $item->add($insert);
			
			/* octopus */
			if(isset($val["kind"]) && $val["kind"] == OWF_DAO_OCTOPUS && isset($item->childs[$var])) {
				
				$zone = $item->name."_".$item->childs[$var]->get_name();
				$octo_insert[$key] = $id;
				
				if($add) {
					$q = new core_db_insert($zone, $octo_insert);
					$this->wf->db->query($q);
				}
				else {
					if($type_changed) {
						$this->wf->db->query(
							new core_db_delete(
								$item->name."_".$item->childs[$type_old]->get_name(),
								array($key => $id)
							)
						);
						$this->wf->db->query(
							new core_db_insert($zone, $octo_insert)
						);
					}
					else {
						$q = new core_db_update($zone, $octo_insert, array($key => $id));
						$this->wf->db->query($q);
					}
				}
			}
			
			/* delayed query */
			foreach($delayed_query as $links) {
				$q = new core_db_delete($links["link"]["table"], array());
				$this->wf->db->query($q);
				$fields = array();
				foreach($links["var"] as $data) {
					$fields[] = array(
						$links["link"]["primary"] => $id,
						$links["link"]["secondary"] => $data
					);
				}
				
				$q = new core_db_insert_multiple($links["link"]["table"], $fields);
				$this->wf->db->query($q);
			}
			
			if(count($error["msgs"]) == 0)
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
				if(isset($val["perm"]))
					$ret = $this->session->check_permission($val["perm"]);
				elseif($val["perm_cb"])
					$ret = call_user_func($val["perm_cb"], $item, $val);
				else
					$ret = true;
				
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
	
	public function gmap() {
		$tpl = new core_tpl($this->wf);
		$tpl->set_vars(array(
			"name" => htmlentities($this->wf->get_var("name")),
			"text" => htmlentities($this->wf->get_var("text")),
			"lat" => floatval($this->wf->get_var("lat")),
			"long" => floatval($this->wf->get_var("long")),
		));
		echo $tpl->fetch('core/dao/gmap');
	}
}
