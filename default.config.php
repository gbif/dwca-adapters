<?php

#This points to the path to the software main loaction.
define('BASE_PATH', '/{path_to_folder}/dwc-adapters/');

$path = '/{path_to_mysql}/mysql/bin'; // MySQL Path for ITIS system call
set_include_path(get_include_path() . PATH_SEPARATOR . $path);

#Used to connect to Mysql ITIS database
$itis = array(
	  "server" => 'localhost'
	, "database" => 'itis'
	, "username" => ''
	, "pass" => ''
	, "remote_file" => 'http://www.itis.gov/downloads/itisMySQLBulk.zip' # Full URL to source dump
);

?>