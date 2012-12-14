<?php

// Include ezSQL core
include_once "ez_sql_core.php";
// Include ezSQL database specific component
include_once "ez_sql_sqlite.php";

//Constants
 $vhost_command = "
<VirtualHost *:80>
  ServerName {domain}
  ServerAlias www.{domain}
  Redirect 301 / http://{forward}
</VirtualHost>
";
function grab_config($config_var){
	$db2 = new ezSQL_sqlite('./','vhosts.db');
	$location_results = $db2->get_results("Select * from config where name='$config_var'");
	$location_row = $db2->get_row();
	return $location_row->command;
}

function restart_apache(){
	$apache_exec = grab_config('apache_location');
	$configtest = shell_exec($apache_exec . ' configtest');
		if (empty($configtest)) {
		    $graceful = shell_exec($apache_exec . ' restart');
		} else {
		    die('Error in the config format. Please contact an administrator.');
		}

}
function generate_vhost_string($domain){
	global $vhost_command;
	$newstring = $vhost_command;
	if ($domain->www_alias <>1){
		$newstring = str_replace("ServerAlias www.{domain}", '', $newstring);
	}
	$newstring = str_replace("{domain}", $domain->domain, $newstring);
	$newstring = str_replace("{forward}", $domain->forward, $newstring);
	return $newstring;
}

function build_vhost($db) {
	$file = grab_config('vhost_location');
	$FileHandle = fopen($file, 'w') or die("can't open file");
	echo "\n Generating the vhost file\n \n";
	$results = $db->get_results("Select * from domain order by domain");
	foreach ($results as $row) {
		echo "writing "  . $row->domain . " to point to " . $row->forward . " \n";
		 fwrite($FileHandle, generate_vhost_string($row));
	}
	fclose($FileHandle);
}


// Initialise database object and establish a connection
// at the same time - db_path / db_name
$db = new ezSQL_sqlite('./','vhosts.db');
//Verify db exists, and create table if it does not
$results = $db->get_results("SELECT name FROM sqlite_master WHERE type='table' AND name like'domain%';");
//$db->debug($results);
if (!$results) {
	echo "Creating initial database";
    $db->query("CREATE TABLE domain(Id integer PRIMARY KEY, domain text UNIQUE NOT NULL, forward  text UNIQUE NOT NULL, www_alias integer)");
}
build_vhost($db);
restart_apache();
?>


	
