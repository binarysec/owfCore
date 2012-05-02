<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * 
 * Config selector
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
require(%MAIN_FILE%);
$ini = "%INI_FILE%";

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * 
 * Launch the engine
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
try {
	$wf = new web_framework($ini);
	$wf->process();
}
catch (wf_exception $e) {
	echo 
		"<pre>\n".
		"* /!\\ Exception:\n";
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
	
	echo "</pre>";
}

