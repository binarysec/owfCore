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

class core_js extends wf_agg {
	var $_core_file;
	
	public function loader($wf) {
		$this->wf = $wf;
		$this->_core_file = $this->wf->core_file();
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Create a link
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function linker($token) {
		$link = $this->_core_file->linker(
			'js',
			$token
		);
		
		$this->wf->core_html()->add_js($token, $link);
		
		return($link);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Create a link pass trought mode
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function pass_linker($link) {
	
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Get last image modification
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function get_last_modified($token) {
		return(
			$this->_core_file->get_last_modified(
				'js',
				$token
			)
		);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Function to show the image
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function show_js($token) {
		return($this->_core_file->echo_data(
			'js',
			$token
		));
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Function to put the image into a buffer
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function get_js($token) {
		return($this->_core_file->get_data(
			'js',
			$token
		));
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Function to get the file size
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function get_size($token) {
		return($this->_core_file->get_file_size(
			'js',
			$token
		));
	}
	
}

?>