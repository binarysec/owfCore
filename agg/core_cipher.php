<?php



class core_cipher extends wf_agg {

	public function loader($wf) {
		$this->wf = $wf;	
	}
	
	public function push($text) {
		return(base64_encode($text));
	}
	
	public function get($text) {
		return(base64_decode($text));
	}
	

}
