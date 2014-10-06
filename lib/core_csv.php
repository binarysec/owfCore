<?php

class core_csv {
	
	private $struct = array();
	
	public function __construct($wf) {
		$this->wf = $wf;
	}
	
	public function load($data) {
		
		if(is_string($data) && file_exists($data)) {
			// str_getcsv does not take care of \n : https://bugs.php.net/bug.php?id=55763&edit=1
			//$this->struct = str_getcsv($this->to_utf8(file_get_contents($data)));
			$lines = file($data, FILE_IGNORE_NEW_LINES);

			foreach($lines as $key => $value) {
				$ld = str_getcsv($value);
				$ldf = array_filter($ld);
				if(!empty($ldf))
					$this->struct[$key] = $ld;
			}
			
		}
		elseif(is_array($data))
			$this->struct = $data;
		else
			throw new wf_exception($this->wf, WF_EXC_PRIVATE, "Wrong data type when loading core_csv");
		
	}
	
	public function save($filepath) {
		
		// check write perms
		$fp = fopen($filepath, 'w');
		
		foreach($this->struct as $fields) {
			fputcsv($fp, $fields);
		}
		
		fclose($fp);
		//int fputcsv ( resource $handle , array $fields [, string $delimiter = "," [, string $enclosure = '"' ]] )
	}
	
	public function get_data() {
		return $this->struct;
	}
	
	private function to_utf8($str) {
		return mb_convert_encoding($str, 'HTML-ENTITIES', "UTF-8");
		return mb_convert_encoding($str, 'UTF-8',
			mb_detect_encoding($str, 'UTF-8, ISO-8859-1', true));
	}
	
}
