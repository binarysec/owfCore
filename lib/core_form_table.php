<?php
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * Web Framework 1                                       *
 * BinarySEC (c) (2000-2008) / www.binarysec.com         *
 * Author: Michael Vergoz <mv@binarysec.com>             *
 * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~         *
 *  Avertissement : ce logiciel est protégé par la       *
 *  loi du copyright et par les traités internationaux.  *
 *  Toute personne ne respectant pas ces dispositions    *
 *  se rendra coupable du délit de contrefaçon et sera   *
 *  passible des sanctions pénales prévues par la loi.   *
 *  Il est notamment strictement interdit de décompiler, *
 *  désassembler ce logiciel ou de procèder à des        *
 *  opération de "reverse engineering".                  *
 *                                                       *
 *  Warning : this software product is protected by      *
 *  copyright law and international copyright treaties   *
 *  as well as other intellectual property laws and      *
 *  treaties. Is is strictly forbidden to reverse        *
 *  engineer, decompile or disassemble this software     *
 *  product.                                             *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

define("CORE_TAB_HALIGN_RIGHT",   200);
define("CORE_TAB_HALIGN_LEFT",    201);
define("CORE_TAB_HALIGN_CENTER",  202);

define("CORE_TAB_VALIGN_TOP",     203);
define("CORE_TAB_VALIGN_MIDDLE",  204);
define("CORE_TAB_VALIGN_BOTTOM",  205);

class core_form_table {
	var $hdr = NULL;
	var $fdr = NULL;
	var $data = array();
	var $row_index = -1;
	var $max_field = 0;
	var $ftr = NULL;
	var $wf = NULL;
	
	public function __construct($wf) {
		$this->wf = $wf;
	}
	
	public function set_header($d) {
		$this->hdr = $d;
	}
	
	public function set_footer($d) {
		$this->ftr = $d;
	}
	
	public function push_row() {
		$this->max_field = 0;
		$this->row_index++;
	}
	
	public function add_field(
			$value,
			$halign=CORE_TAB_HALIGN_LEFT,
			$border=TRUE,
			$size=NULL,
			$valign=CORE_TAB_VALIGN_MIDDLE
		) {
		$into = &$this->data[$this->row_index];
		if(!is_array($into))
			$into = array();
		$into[] = array(
			$value,
			$halign,
			$border,
			$size,
			$valign
		);
		$this->max_field++;
	}
	
	public function renderer($width=NULL) {

		if($width) {
			$ret = '<table class="interact_table" width="'.
				$width.
				'" border="0" cellspacing="0" cellpadding="0">';
		}
		else
			$ret = '<table class="interact_table" border="0" cellspacing="0" cellpadding="0">';
		
		if($this->hdr != NULL) {
			$ret .= "<tr>\n".
				'<td '.
				'style="border-right:1px solid #000000; '.
				'border-bottom:1px solid #000000;" '.
				'colspan='.$this->max_field.">".$this->hdr."</td>\n".
				"</tr>\n";
		}
		
		/* affiche les résults */
		foreach($this->data as $row) {
			$ret .= "<tr>\n";
			foreach($row as $fk => $field) {


				$div = 'class="interact_table_td" ';
				if($field[2] == FALSE)
					$div .= 'style="border-right:0px solid #000000;" ';
					
				$td = '<td';
				
				switch($field[1]) {
					case CORE_TAB_HALIGN_RIGHT: $ha = "right"; break;
					case CORE_TAB_HALIGN_LEFT: $ha = "left"; break;
					case CORE_TAB_HALIGN_CENTER: $ha = "center"; break;
				}
				
				/* taille */
				if($field[3] != NULL) 
					$td .= ' width="'.$field[3].'"';
				
				$ret .= $td.' align="'.$ha.'" '.$div.'>';
				$ret .= $field[0];
				$ret .= "</td>\n";
				
			}
		
			$ret .= "</tr>\n";
		}
		
		if($this->ftr != NULL) {
			$ret .= "<tr>\n".
				'<td valign="middle" '.
				'style="border-right:1px solid #000000; '.
				'border-bottom:1px solid #000000;" '.
				'colspan='.$this->max_field.">".$this->ftr."</td>\n".
				"</tr>\n";
		}
		
		$ret .= "</table>";
	
		return($ret);

	}	
}

?>