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

class core_table {
	public function __construct($param=NULL) {
		
	}

	public function set($row, $cols, $data, $param=NULL) {
	
	}

	protected function build_attribs($names) {
		$attribs = '';
		foreach($names as $name)
			if(array_key_exists($name, $this->attribs)) {
				if(is_bool($this->attribs[$name]))
					$value = $this->attribs[$name] ? 'true' : 'false';
				else
					$value = $this->attribs[$name];
				$attribs .= ' '.$name.'="'.$value.'"';
			}
		return($attribs);
	}
	
}

?>