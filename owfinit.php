#!/usr/bin/php
<?php

/* find main.php */
$main_file = dirname(__FILE__)."/main.php";
if(!is_file($main_file)) {
	echo "* Can not find $main_file\n";
	exit(0);
}
require($main_file);

/* get install dir */
$install_dir = dirname(__FILE__)."/install";

function question($q, $default, $help=NULL) {
	echo "* $q: ";
	$fd = fopen("php://stdin","r"); 
	$r = fread($fd, 2048);
	fclose($fd);
	if(strlen($r) == 1)
		return($default);
	return(trim($r));
}

function replace($patterns, $src, $dst) {
	$pat = array();
	$rep = array();
	
	foreach($patterns as $k => $v) {
		$pat[] = "/%".strtoupper($k)."%/";
		$rep[] = $v;
	}

	$ret = preg_replace(
		$pat,
		$rep,
		file_get_contents($src)
	);

	file_put_contents(
		$dst,
		$ret
	);
}

echo "* Welcome to Open Web Framework installer v".WF_VERSION."\n* Ctrl+C to exit\n*\n";
$home = getenv("HOME");
$config = array();

$config['chroot'] = question("Use chroot environnement ? [Y/n]", "y");

$config['site_name'] = question("Site name [default]", "default");

$config['public_html'] = question("Public HTML repository ? [$home/public_html]", $home."/public_html");

$config['data_dir'] = question("Data repository ? [$home/data]", $home."/data");

$config['cli_ini'] = question("INI configuration file ? [$home/data/config.ini]", $home."/data/config.ini");

echo "*\n";

/* mysql */
$config['mysql_host'] = question("MySQL server host [localhost]", "localhost");
$config['mysql_port'] = question("MySQL server port [3306]", "3306");
$config['mysql_user'] = question("MySQL username", "");
$config['mysql_password'] = question("MySQL password", "");
$config['mysql_dbname'] = question("MySQL dbname", "");

/* mysql administrative */
$r = question("Automatic create user/database [Y/n]", "y");
if(strtolower($r[0]) == 'y')
	$config['mysql_root_password'] = question("MySQL root password", "");
else
	$config['mysql_root_password'] = false;

$config['ini_file'] = $config['data_dir']."/config.ini";

echo "* Please check config before writing:\n";
foreach($config as $k => $v) 
	echo "*\t $k = $v\n";

$r = question("Confirm ? [n/Y]", "n");
if(strtolower($r[0]) != 'y') {
	echo "* exiting\n";
	exit(0);
}

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 *
 * Process installation
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
$config['core_dir'] = dirname(__FILE__);

system("mkdir -p $config[public_html]");
system("mkdir -p $config[data_dir]");

/* config ini */
replace($config, "$install_dir/config.ini", $config['ini_file']);
echo "* Configure INI: $config[ini_file]\n";

/* terminal module */
$module_file = $config['data_dir']."/module.php";
replace($config, "$install_dir/module.php", $module_file);
echo "* End-point module: $module_file\n";

/* Web index */
$index_file = $config['public_html']."/index.php";
replace($config, "$install_dir/index.php", $index_file);
echo "* Creating index.php: $index_file\n";

/* links */
echo "* Linking ...\n";
if(is_link("$home/.owf.cli.ini"))
	echo "* Warning: $home/.owf.cli.ini seems to exist\n";
else
	system("ln -s $home/.owf.cli.ini $config[ini_file]");

if(is_link("$home/.owf.core.link"))
	echo "* Warning: $home/.owf.core.link seems to exist\n";
else
	system("ln -s $home/.owf.core.link $main_file");

/* creating MySQL database */
if($config['mysql_root_password'] != false) {
	replace($config, "$install_dir/initdb.mysql.sql", $config['data_dir']."/initdb.mysql.sql");
	echo "* Prepare to init MySQL: ...";
	
	$cmd = "mysql ".
		"-h $config[mysql_host] ".
		"-P $config[mysql_port] ".
		"-u root -p$config[mysql_root_password] <  $config[data_dir]/initdb.mysql.sql";
	
	system($cmd);
	
	unlink("$config[data_dir]/initdb.mysql.sql");
	
	echo "OK\n";
}
