<?php

//email 
if ( ! filter_var($_POST["email"], FILTER_VALIDATE_EMAIL)){
    die("Email is required");
}
//phone
if (preg_match("/[a-z]", $_POST["phone_number"])){
    die("Phone number cannot contain any letters");
}
//remove special chars from phone number
preg_replace('/[^\dxX]/', '', $_POST["phone_number"]);

//password 
if (strlen($_POST["password"]) < 12){
    die("Password doesn't meet minimum length requirements of 12 Characters");
}
if (! preg_match("/[a-z]i", $_POST["password"])){
    die("Password doesn't have enough letters");
}
if (! preg_match("/[0-9]", $_POST["password"])){
    die("Password doesn't have enough numbers");
}
//confirm password 
if ($_POST["password"] !== $_POST["confirm-password"]){
    die("Password do not match");
}
//hash password 
$password_hash = password_hash($_POST["password"], PASSWORD_DEFAULT); 


//gets access to db 
$mysqli = require __DIR__ . "/database.php";

//inserts values into db 
$sql = "INSERT INTO user (email, phone_number, password_hash)
        values (?, ?,?)";
$stmt = $mysqli->stmt_init();

if( ! stmt->prepare($sql)) {
    die("Error with DB: " . $mysqli->error);
}

$stmt->bind_param("sis", $_POST["email"],$_POST["phone-number"], $password_hash);

//executes and takes new account to patient info page and throws error if there is an issue with entry 
if ($stmt->executes()){
    header("Location: patient-inofrmation.html");
    exit;
}
else{
    die($mysqli->error . " " . $mysqli->errno);
}
?>