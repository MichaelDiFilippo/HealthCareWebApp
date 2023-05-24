<?php

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$host = 'healthcaredb.cbda4iwuslvo.us-west-2.rds.amazonaws.com';
$port = 3306;
$db = 'healthcare';  // The name of your database on RDS
$user = 'Dr_Adam';  // The username you've set for your RDS instance
$pass = '##!!IHateApples1776!!##';  // The password you've set for your RDS instance

// Create connection
$mysqli = mysqli_connect($host, $user, $pass, $db, $port);

// Check connection
if (!$mysqli) {
    die("Connection failed: " . mysqli_connect_error());
}
echo "Connected successfully";
return $mysqli;
?>


