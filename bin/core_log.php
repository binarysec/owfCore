<?php

class core_log_bin extends web_framework {
	private $gzip = "/bin/gzip";
	private $gunzip = "/bin/gunzip";
	private $core_log;
	
	public function cli() {
		$this->cl = $this->core_log();
		
		$exlude_time = mktime(0, 0, 0);
		
		/* get logs */
		$logs = $this->cl->get();
		foreach($logs as $log) {
			$base_dir = "/var/logs/$log[channel]";
			$current_dir = $base_dir."/_current";
			$archives_dir = $base_dir."/_archives";
			
			/* scan files */
			$files = $this->scandir($current_dir);
			$matches = array();
			foreach($files as $file) {
				preg_match("/^([0-9]+)\.log$/", $file, &$matches);
				if($matches[1] && $matches[1] != $exlude_time)
					$this->store_gzip($log, $file, $base_dir, $matches[1]);
			}
		}
	}
	
	private function store_gzip($log, $file, $base_dir, $time) {
		$current_file = $base_dir."/_current/$file";
		$archives_file = $base_dir."/_archives/$file".".gz";
		$srcfile = $this->locate_file($current_file);
		$dstfile = $this->get_last_filename($archives_file);
		$this->create_dir($dstfile);
		$size = stat($srcfile);
		$size = $size["size"];
		system("gzip -c $srcfile > $dstfile");
		unlink($srcfile);
		$this->cl->set_archive($log["id"], $time, $size);
		echo "Channel $log[channel] need a snapshot at $archives_file\n";
	}

}

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * 
 * Launch the engine
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
try {
	$wf = new core_log_bin($ini);
	$wf->cli();
}
catch (wf_exception $e) {
	echo "/!\\ Exception:\n";
	if(is_array($e->messages)) {
		$i = 0;
		foreach($e->messages as $v) {
			echo "* ($i) ".$v."\n";
			$i++;
		}
	}
	else {
		echo $e->messages."\n";
	}
}

