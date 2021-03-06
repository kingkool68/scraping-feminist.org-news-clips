<?php
include 'db-config.php';
include 'medoo.min.php'; // See http://medoo.in/

global $db;
// Instantiate a new database connection via the medoo() class
$db = new medoo(array(
	// required
	'database_type' => 'mysql',
	'database_name' => DB_NAME,
	'server' => DB_HOST,
	'username' => DB_USER,
	'password' => DB_PASSWORD,
	'charset' => DB_CHARSET,
));
