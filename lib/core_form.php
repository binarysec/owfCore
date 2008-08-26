<?php
 
/* (c) mykii#@! */

define("CORE_FORM_TEXT",     100);
define("CORE_FORM_INPUT",    101);
define("CORE_FORM_PASS",     102);
define("CORE_FORM_SELECT",   103);
define("CORE_FORM_HIDDEN",   104);
define("CORE_FORM_CHECKBOX", 105);
define("CORE_FORM_FILE",     106);

class core_form {
	var $wf;
	var $el = array();
	var $errors = array();
	var $p_link = NULL;
	var $title = NULL;
	var $b_submit = NULL;
	var $b_reset = NULL;
	
	var $is_multipart = FALSE;

	public function __construct($wf) {
		$this->wf = $wf;
	}
	
	public function load($link, $title=NULL, $submit=NULL, $reset=NULL) {
		$this->p_link = $link;
		$this->title = $title;
		$this->b_submit = $submit;
		$this->b_reset = $reset;
	}

	var $division = 0;
	var $printable_data = 0;
	
	public function add_element(
			$key,
			$type,
			$text,
			$default=NULL,
			$value=NULL,
			$size=30
		) {
	
		if($type != CORE_FORM_HIDDEN)
			$this->printable_data++;
		
		if($type == CORE_FORM_FILE)
			$this->is_multipart = TRUE;
			
		$insert = array(
			"K" => $key,
			"TYPE" => $type,
			"TEXT" => $text
		);
		if($value != NULL)
			$insert["VAL"] = $value;
		if($default != NULL)
			$insert["DFT"] = $default;
		if($size != NULL)
			$insert["SIZE"] = $size;
			
		$this->el[$key] = $insert;
		
		return(TRUE);
	}
	
	public function load_vars() {
		
		$ret = array();
		reset($this->el);
		foreach($this->el as $k => $v) {
			$ret[$k] = $this->load_var($k);
			
			/* si l'input est de type select on 
			   vérifie les elements :) */
			if($v["TYPE"] == CORE_FORM_SELECT) {
				if(!isset($v["VAL"][$ret[$k]])) {
// 					return(array());
				}
				
			}
		}
		
		return($ret);
	}
	
	public function load_var($name) {
		global $_GET;
		global $_POST;
		if(isset($_GET[$name]))
			return($_GET[$name]);
		else if(isset($_POST[$name]))
			return($_POST[$name]);
		return(NULL);
	}
	
	public function add_error($str) {
		array_push($this->errors, $str);
	}
	
	public function get_error() {
		return($this->errors);
	}
	
	public function set_division($div) {
		$this->division = $div;
	}
	
	var $no_form = FALSE;
	public function no_form() {
		$this->no_form = TRUE;
	}
	
	public function renderer() {
		
		$tab = $this->wf->a_core_form_table();
		
		/* construit le header */
		$hdr = NULL;
		if($this->title != NULL) {
			$hdr = '<div class="interact_form_header">'.
				$this->title.
				' :</div>';
			
		}
		reset($this->errors);
		foreach($this->errors as $v) {
			$hdr .= '<div class="interact_form_error">* '.
				htmlspecialchars($v).
				'</div>';
		}
		$tab->set_header($hdr);
		
		/* vérification si multipart est nécessaire */
		if($this->is_multipart)
			$multipart = 'enctype="multipart/form-data"';
			
		/* construit le rendu */
		if(!$this->no_form)
			$tab_r = '<form '.$multipart.' method="post" action="'.$this->p_link.'">';
			
		$tab_r .= '<div class="interact_form">';
		
		$init = TRUE;
		reset($this->el);
		$index = 0;
		$tab->push_row();
		foreach($this->el as $k => $v) {
			$text = $v["TEXT"];

			/* check la division */
			if($this->division > 1) {
				if($this->printable_data/$this->division > $index)
					$tab->push_row();
				else
					$index = 0;
			}
			else
				$tab->push_row();
				
			$index++;
			
			$post_val = $this->load_var($k);
			if($post_val != FALSE && strlen($v["DFT"]) <= 0)
				$v["DFT"] = $post_val;
			$v["DFT"] = htmlspecialchars($v["DFT"]);
			
			switch($v["TYPE"]) {
				case CORE_FORM_TEXT:
					
					$tab->add_field(
						$text,
						CORE_TAB_HALIGN_RIGHT,
						FALSE
					);
					
					$text = $v["DFT"];
				
					$tab->add_field(
						$text,
						CORE_TAB_HALIGN_LEFT
					);
					break;
					
				case CORE_FORM_INPUT:
					if($v["SIZE"] != NULL) {
						$size = ' size="'.
							$v["SIZE"].
							'"';
					}
					
					$i = '<input '.
						$size.
						' type="text" name="'.
						$k.
						'" value="'.
						$v["DFT"].
						'">'."\n";
					
					$tab->add_field(
						$text,
						CORE_TAB_HALIGN_RIGHT,
						FALSE
					);
					$tab->add_field(
						$i,
						CORE_TAB_HALIGN_LEFT
					);
					break;
					
				case CORE_FORM_PASS:
					if($v["SIZE"] != NULL) {
						$size = ' size="'.
							$v["SIZE"].
							'"';
					}
					
					$i = '<input '.
						$size.
						' type="password" name="'.
						$k.
						'">'."\n";
					
					$tab->add_field(
						$text,
						CORE_TAB_HALIGN_RIGHT,
						FALSE
					);
					$tab->add_field(
						$i,
						CORE_TAB_HALIGN_LEFT
					);
					
					break;
				
				case CORE_FORM_SELECT:
					$i = '<select '.
						' name="'.$k.'">'."\n";
					
					reset($v["VAL"]);
					foreach($v["VAL"] as $vk => $vv) {
						if($vk == $v["DFT"])
							$selected = " selected";
						else 
							$selected = NULL;
						
						$i .= '<option value="'.
							$vk.
							'"'.
							$selected.
							'>'.
							$vv.
							"</option>\n";
						
						
					}
					$i .= "</select>\n";

					$tab->add_field(
						$text,
						CORE_TAB_HALIGN_RIGHT,
						FALSE
					);
					$tab->add_field(
						$i,
						CORE_TAB_HALIGN_LEFT
					);
					break;
					
				case CORE_FORM_HIDDEN:
					$i = '<input type="hidden" name="'.
						$k.
						'" value="'.
						$v["DFT"].
						'">'."\n";
					
					$tab_r .= $i;
					break;

				case CORE_FORM_CHECKBOX:
					if($v["DFT"] == TRUE)
						$checked = " checked";
					else
						$checked = NULL;
					
					$i = '<input type="checkbox" name="'.
						$k.
						'" value="'.
						$v["VAL"].
						'"'.
						$checked
						.'>'."\n";
					
					$tab->add_field(
						$text,
						CORE_TAB_HALIGN_RIGHT,
						FALSE
					);
					$tab->add_field(
						$i,
						CORE_TAB_HALIGN_LEFT
					);
					
					break;

				case CORE_FORM_FILE:
					if($v["SIZE"] != NULL) {
						$size = ' size="'.
							$v["SIZE"].
							'"';
					}
					
					$i = '<input '.
						$size.
						' type="file" name="'.
						$k.
						'" value="'.
						$v["DFT"].
						'">'."\n";
					
					$tab->add_field(
						$text,
						CORE_TAB_HALIGN_RIGHT,
						FALSE
					);
					$tab->add_field(
						$i,
						CORE_TAB_HALIGN_LEFT
					);
					break;
					
			}
		
		}
		
		if(!$this->no_form) {
			$buf = '<div class="interact_form_footer">';
			if($this->b_submit) {
				$b_submit = ' value="'.$this->b_submit.'"';
				$buf .= '<input class="CORE_form_submit" type="submit"'.$b_submit.'>';
			}
			if($this->b_reset) {
				$b_reset = ' value="'.$this->b_reset.'"';
				$buf .= '<input class="CORE_form_submit" type="reset"'.$b_reset.'> ';
			}
			$buf .= '</div>';
		}
		
		$tab->set_footer($buf);
		
		$tab_r .= $tab->renderer();
		$tab_r .= "</div>\n";
		
		if(!$this->no_form)
			$tab_r .= "</form>\n";
		
		return($tab_r);
	}
	
}

?>