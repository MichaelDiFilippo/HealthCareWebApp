<?php
$host = 'healthcaredb.cbda4iwuslvo.us-west-2.rds.amazonaws.com';
$port = 3306;  
$db = 'healthcaredb';  // The name of your database on RDS
$user = 'Dr_Adam';  // The username you've set for your RDS instance
$pass = '##!!IHateApples1776!!##';  // The password you've set for your RDS instance

// Create connection
$link = mysqli_connect($host, $user, $pass, $db, $port);

// Check connection
if (!$link) {
    die("Connection failed: " . mysqli_connect_error());
}
echo "Connected successfully";
?>
