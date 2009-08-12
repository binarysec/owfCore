<?php

/* search for INI file */
$ini = getenv("HOME")."/.owf.cli.ini";
if(!file_exists($ini)) {
	echo "Please create INI file at $ini\n";
	exit(0);
}
	

/* search for core link */
$core_link = getenv("HOME")."/.owf.core.link";
if(!file_exists($core_link)) {
	echo "Please create core main.php link at $core_link\n";
	exit(0);
}
	
/* opening framework */
require($core_link);

if(!$_SERVER["argv"][1]) {
	echo "Please specify an OWF bin file to run\n";
	exit(0);
}

require($_SERVER["argv"][1]);