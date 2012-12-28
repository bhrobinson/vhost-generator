<?php
//Constants
 $vhost_command = "
<VirtualHost *:80>
  ServerName {domain}
  ServerAlias www.{domain}
  Redirect 301 / http://{forward}
</VirtualHost>
";
function grab_config($config_var){
	$db = new SQLite3('vhosts.db');
	$location_results = $db->query("Select * from config where name='$config_var'");
	$name=$location_results->fetcharray();
	return $name[command];
    $location_row->command;
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
	if ($domain['www_alias']<>1){
		$newstring = str_replace("ServerAlias www.{domain}", '', $newstring);
	}
	$newstring = str_replace("{domain}", $domain['domain'], $newstring);
	$newstring = str_replace("{forward}", $domain['forward'], $newstring);
	return $newstring;
}

function build_vhost($db) {
	$file = grab_config('vhost_location');
    $FileHandle = fopen($file, 'w') or die("can't open file");
	echo "\n Generating the vhost file\n \n";
	$result=$db->query("SELECT * FROM domain");
	$x=1;
	while ($row = $result->fetchArray()) {
		echo "writing "  . $row['domain'] . " to point to " . $row['forward'] . " \n";
		fwrite($FileHandle, generate_vhost_string($row));
	}
	fclose($FileHandle);
}


// Initialise database object and establish a connection
// at the same time - db_path / db_name
$db = new SQLite3('vhosts.db');
//Verify db exists, and create table if it does not
$results = $db->query("SELECT name FROM sqlite_master WHERE type='table';");

if ((count($results->fetcharray()))<2) {
	echo "Creating initial database";
    $db->exec("CREATE TABLE domain(Id integer PRIMARY KEY, domain text UNIQUE NOT NULL, forward  text UNIQUE NOT NULL, www_alias integer)");
	$db->exec("CREATE TABLE config(name text UNIQUE NOT NULL, command text UNIQUE NOT NULL)");
	$db->exec('insert into config (name,command) values ("apache_location","/usr/sbin/apache")');
	$db->exec('insert into config (name,command) values ("vhost_location","vhosts.conf")');
}
build_vhost($db);
//restart_apache();

?>


	
