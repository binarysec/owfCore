<?php

class core_lock extends wf_agg {

	public function loader($wf) {
		
		$this->lock_dir = $this->wf->get_last_filename("core/locks");
		$this->wf->create_dir($this->lock_dir."/pad");
		
	}
	
	public function try_lock($group, $resource, $type, $verbose = true) {
	
		$lf = $this->lock_dir."/$group-$resource.lock";
		
		$fp = fopen($lf, 'a+');
		if(!$fp) {
			if($verbose)
				echo "Can not open $lf\n";
			return(false);
		}
		
		if(flock($fp, LOCK_EX)) {
			$data = fgets($fp);
			$serial = @unserialize($data);
			if(!is_array($serial)) {
				$serial = array(
					"group" => $group,
					"resource" => $resource,
					"type" => $type,
					"lock" => false,
					"pid" => -1,
				);
			}
			
			/* site is locked */ 
			if($serial["lock"] == true) {
				/* control the pid if it is dead */
				$pf = "/proc/$serial[pid]";
				if(!file_exists($pf)) {
					if($verbose)
						echo "*\n".
							"* THE RESOURCE $serial[resource] UNDER GROUP $serial[group]\n".
							"* IS ACTUALY LOCKED BY A DEAD PROCESS n°$serial[pid]\n".
							"* Recreating lock\n";
				}
				else {
					flock($fp, LOCK_UN);
					fclose($fp);
					return(false);
				}
			}
			
			/* lock the file */
			$serial["lock"] = true;
			$serial["pid"] = getmypid();
				
			/* write the content */
			ftruncate($fp, 0);
			fputs($fp, serialize($serial));
			flock($fp, LOCK_UN);
		}
		else {
			fclose($fp);
			return(false);
		}
		
		fclose($fp); 

		return(true);
		
	}

	public function unlock($group, $resource) {
		$lf = $this->lock_dir."/$group-$resource.lock";
		if(file_exists($lf))
			unlink($lf);
	}
}

