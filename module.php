<?php
 
class core extends wf_module {
	public function __construct($wf) {
		$this->wf = $wf;
	}
	
	public function get_name() { return("core"); }
	public function get_description()  { return("Web Framework Core"); }
	public function get_banner()  { return("WFCore/1.0"); }
	public function get_version() { return("1.0"); }
	public function get_authors() { return("Michael VERGOZ"); }
	public function get_depends() { return(NULL); }
}

?>
