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


class core_mime extends wf_agg {
	var $ini = NULL;
	
	public function loader($wf) {
		$this->wf = &$wf;
		
		/* prend le fichier ini */
		$file = dirname(dirname(__FILE__))."/var/mime.ini";

		$this->ini = parse_ini_file($file);
	}
	
	public function get_mime($file) {
		/* find extension */
		$ext = NULL;
		for($a=strlen($file)-1; $a>0; $a--) {
			$c = $file[$a];
			if($c == ".")
				break;
			$ext .= $c;
		}
		$ext  = strrev($ext);
		
		/* search */
		if($this->ini[$ext])
			return($this->ini[$ext]);
		
		/* if not know */
		if(function_exists("mime_content_type")) 
			$mime = mime_content_type($file);
		else 
			$mime = NULL;
			
		if(!$mime)
			$mime = 'application/octet-stream';
		return($mime);
	}
	
	
}

?>