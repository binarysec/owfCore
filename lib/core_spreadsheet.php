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

class core_spreadsheet {
	private $attribs;
	private $data = array();
	
	public function __construct($attribs=NULL) {
		$this->attribs = $attribs;
	}

	public function row_attribs($row, $attribs) {
		if(!is_array($this->data[$row]))
			$this->data[$row] = array(array());
		$this->data[$row][1] = $attribs;
	}
	
	public function set($row, $cols, $data, $attribs=NULL) {
		if(!is_array($this->data[$row]))
			$this->data[$row] = array(array());
		$this->data[$row][0][$cols] = array(
			$data,
			$attribs
		);
	}
	
	private $allow_head = FALSE;
	private $allow_foot = FALSE;
	
	public function allow_head() {
		$this->allow_head = TRUE;
	}
	
	public function allow_foot() {
		$this->allow_foot = TRUE;
	}
	
	public function renderer($sr=0, $sc=0) {
		$attribs = $this->build_attribs($this->attribs);
		$buf = "<table$attribs>\n";

		$ahead = $this->allow_head;
		$afoot = $this->allow_foot;
		$abody = TRUE;
		
		for($r=$sr; $r<count($this->data); $r++) {
			$rv = &$this->data[$r];
			
			if($ahead && $r == $sr) {
				$buf .= "<thead>\n";
			}
			else if($afoot && $r == count($this->data)-1) {
				$buf .= "<tfoot>\n";
			}
			else if($abody) {
				$buf .= "<tbody>\n";
				$abody = FALSE;
			}
			
			$attribs = $this->build_attribs($rv[1]);
			$buf .= "<tr$attribs>\n";
	
			for($c=$sc; $c<count($rv[0]); $c++) {
				$cv = &$rv[0][$c];
				
				if($ahead && $r == $sr) 
					$use = 'th';
				else
					$use = 'td';
					
				$attribs = $this->build_attribs($cv[1]);
				$buf .= "<$use$attribs>$cv[0]</$use>\n";
			}

			$buf .= "</tr>\n";
			
			if($ahead && $r == $sr)
				$buf .= "</thead>\n";
			else if($afoot && $r == count($this->data)-1)
				$buf .= "</tfoot>\n";
			else if(!$abody && $r == count($this->data)-1)
				$buf .= "</tbody>\n";
		}
		
		$buf .= "</table>";
		return($buf);
	}
	
	private function build_attribs($data) {
		$attribs = '';
		if(count($data) <= 0)
			return(NULL);
		foreach($data as $name => $value) {
			if(is_bool($value))
				$rvalue = $value ? 'true' : 'false';
			else
				$rvalue = $value;
			$attribs .= ' '.$name.'="'.$rvalue.'"';
		}
		return($attribs);
	}
	
}

?>