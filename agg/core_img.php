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
	
	public function loader($wf, $position) {
		$this->wf = $wf;
	}
	
	public function construct_path($mod, $file) {
		/* if module doesn't exist return null */
		if(!isset($this->wf->modules[$mod])) {
			//~ trigger_error('le module <<'.$this->wf->modules[$mod].'>> n\'existe pas', WF_E_WARNING);
			return NULL;
		}

		/* build file path and base directory path */
		$base = realpath($this->wf->modules[$mod][0].'/var/img/');
		$path = realpath($this->wf->modules[$mod][0].'/var/img/'.$file);

		/* if file doesn't exist return null */
		if(!file_exists($path)) {
			//~ trigger_error('le fichier <<'.$path.'>> n\'existe pas', WF_E_WARNING);
			return NULL;
		}

		/* directory transversal detection */
		if(substr($path, 0, strlen($base)) != $base) {
			//~ trigger_error('tentative de directory traversal détectée', WF_E_ERROR);
			return NULL;
		}

		return $path;
	}

	public function get_last_modified($path) {
		/* last modification date of the file */
		$mtime = filemtime($path);

		/* format the date */
		$lastmod = date("D, d M Y H:i:s \G\M\T", $mtime);

		return($lastmod);
	}

	public function get_mime_type($path) {
		/* image type detection */
		$image_type = exif_imagetype($path);

		/* Mime type to use in the HTTP header Content-type */
		$mime_type = image_type_to_mime_type($image_type);

		return($mime_type);
	}

	public function get_content($path) {
		return(file_get_contents($path));
	}
	
}

?>