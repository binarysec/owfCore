#!/usr/bin/php
<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 *
 * API
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

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

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 *
 * auto configure
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

/* find main.php */
$main_file = dirname(__FILE__)."/main.php";
if(!is_file($main_file)) {
	echo "* Can not find $main_file\n";
	exit(1);
}
require($main_file);

/* get install dir */
$install_dir = dirname(__FILE__)."/install";
if(!is_dir($install_dir)) {
	echo "* Can not find $install_dir\n";
	exit(1);
}

echo "\n* Welcome to Open Web Framework installer v".WF_VERSION."\n* Ctrl+C to exit\n*\n";
echo "* Auto-detecting configuration :\n";

system("mysql --version 2>/dev/null 1>&2", $hasmysql);
$hasmysql = !$hasmysql;
echo "* ".($hasmysql ? "Detected MySQL driver, using it" : "/!\ MySQL client library not found : database won't be initialized")."\n";
echo "*\n";

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 *
 * manual configure
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

$home = getenv("HOME");
$config = array('main_file' => $main_file);

$dft_dir = question("Main directory of your web application [$home]", $home);
$dft_sitename = end(explode("/", $dft_dir));
$dft_sitename = $config['site_name'] = question("Site name [$dft_sitename]", "$dft_sitename");
$config['data_dir'] = question("Data repository ? [$dft_dir/data]", $dft_dir."/data");
$config['public_html'] = question("Public HTML repository ? [$dft_dir/public_html]", $dft_dir."/public_html");
$config['cli_ini'] = question("INI configuration file ? [$dft_dir/data/config.ini]", $dft_dir."/data/config.ini");

if($hasmysql) {
	echo "*\n";

	/* mysql */
	$config['mysql_host'] = question("MySQL server host [localhost]", "localhost");
	$config['mysql_port'] = question("MySQL server port [3306]", "3306");
	$config['mysql_user'] = question("MySQL username [$dft_sitename]", "$dft_sitename");
	$config['mysql_password'] = question("MySQL password", "");
	$config['mysql_dbname'] = question("MySQL dbname [$dft_sitename]", "$dft_sitename");

	/* mysql administrative */
	$r = question("Automatic create user/database [Y/n]", "y");
	if(strtolower($r[0]) == 'y')
		$config['mysql_root_password'] = question("MySQL root password", "");
	else
		$config['mysql_root_password'] = false;
}

$config['ini_file'] = $config['data_dir']."/config.ini";

echo "* Please check config before writing:\n";
foreach($config as $k => $v) 
	echo "*\t $k = $v\n";

$r = question("Confirm ? [n/Y]", "y");
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
system("mkdir -p $config[data_dir]/var");

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
	echo "* * Warning: $home/.owf.cli.ini seems to already exist, aborting\n";
else
	system("ln -s $config[ini_file] $home/.owf.cli.ini");

if(is_link("$home/.owf.core.link"))
	echo "* * Warning: $home/.owf.core.link seems to already exist, aborting\n";
else
	system("ln -s $main_file $home/.owf.core.link");

/* Fixing permissions */
echo "* Fixing permissions: ...";
$uid = getmyuid();
system("chown -R $uid:$uid $config[public_html]");
system("chown -R $uid:$uid $config[data_dir]");
system("chmod 777 -R $config[data_dir]/var");
echo " OK\n";

/* creating MySQL database */
if($hasmysql && $config['mysql_root_password'] != false) {
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

echo "*\n* Installation done. Please check your configuration at $config[ini_file].\n";
echo "* If you try to log on the interface, a default user will be created. Credentials can be found in $config[data_dir]/var/logs/core_log/\n";

if($hasmysql) {
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * 
	 * Launch the engine
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	//try {
		//$wf = new web_framework($config['ini_file']);
		//echo "Tentative d'ajout d'un utilisateur";
	//}
	//catch (wf_exception $e) {
		//echo "Impossible de terminer la création de l'utilisateur";
	//}
}