<?php
 
class wfm_%SITE_NAME% extends wf_module {
	public function __construct($wf) {
		$this->wf = $wf;
	}
	public function get_name() { return("%SITE_NAME%"); }
	public function get_description()  { return("%SITE_NAME%"); }
	public function get_banner()  { return("%SITE_NAME%"); }
	public function get_version() { return("%SITE_NAME%"); }
	public function get_authors() { return(array("OWF")); }
	public function get_depends() { return(NULL); }
	public function get_actions() {
		return(array(
		));
	}
}
