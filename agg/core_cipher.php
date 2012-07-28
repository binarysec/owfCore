<?php



class core_cipher extends wf_agg {

	public function loader($wf) {
		$this->wf = $wf;
		
		/* register session preferences group */
		$this->core_pref = $this->wf->core_pref()->register_group(
			"cipher", 
			"HTTP Security"
		);
		
		$this->do_gzip = $this->core_pref->register(
			"gzip", "GZIP compression", 
			CORE_PREF_BOOL, true
		);
		
		$this->do_cipher = $this->core_pref->register(
			"cipher", "Request Ciphering", 
			CORE_PREF_BOOL, true
		);
		
		
		$this->thekey = $this->core_pref->register(
			"thekey", "Request Ciphering", 
			CORE_PREF_HIDDEN, $this->wf->generate_password()
		);

	}
	
	public function encode($text) {
		$t = $text;
// 		if($this->do_gzip)
// 			$t = gzdeflate($t);
		$t = base64_encode($t);
		return($t);
	}
	
	public function decode($text) {
		$t = base64_decode($text);
// 		if($this->do_gzip)
// 			$t = gzinflate($t);
		return($t);
	}
	
	public function get_var($varname) {
		return($this->decode($this->wf->get_var($varname)));
	}
}
