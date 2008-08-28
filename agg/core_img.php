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

class core_img extends wf_agg {
	var $_core_file;
	
	public function loader($wf) {
		$this->wf = $wf;
		$this->_core_file = $this->wf->core_file();
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Create a link image
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function linker($link) {
		$link = $this->_core_file->linker(
			'img',
			$link
		);
		return($link);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Get last image modification
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function get_last_modified($token) {
		return(
			$this->_core_file->get_last_modified(
				'img',
				$token
			)
		);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Function to show the image
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function show_image($token) {
		return($this->_core_file->echo_data(
			'img',
			$token
		));
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Function to put the image into a buffer
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function get_image($token) {
		return($this->_core_file->get_data(
			'img',
			$token
		));
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Function to get the type of image file
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function get_mime_type($token) {
		$filename = $this->_core_file->get_filename(
			'img',
			$token
		);
		if(!$filename)
			return(NULL);
			
		/* détermine le type de l'image */
		$image_type = exif_imagetype($filename);

		/* détermine le type Mime à utiliser 
		   dans l'en-tête HTTP Content-type */
		$mime_type = image_type_to_mime_type($image_type);

		return($mime_type);
	}
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *
	 * Function to get the file size
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	public function get_size($token) {
		return($this->_core_file->get_file_size(
			'img',
			$token
		));
	}
	
}

?>