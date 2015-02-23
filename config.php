<?php

#This points to the path to the software main loaction.
define('BASE_PATH', '/var/local/indexing/dwc-adapters/');

# http://www.itis.gov/downloads/
#Used to connect to Mysql ITIS database
$itis = array(
	  server => 'nerf.gbif.org'
	, database => 'itis'
	, username => 'itis'
	, pass => 'sdF6gre4'
	, remote_file => 'http://www.itis.gov/downloads/itisMySQLBulk.zip' # full url
);
?>

