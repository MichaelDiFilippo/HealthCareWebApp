<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$mysqli = include __DIR__ . "/database.php";
session_start();

// Validate input
if (!filter_var($_POST["email"], FILTER_VALIDATE_EMAIL)) {
    die("Email is required");
}

// Check phone number
if (preg_match("/[a-z]/", $_POST["phone_number"])) {
    die("Phone number cannot contain any letters");
}

// Remove special chars from phone number
$_POST["phone_number"] = preg_replace('/[^\dxX]/', '', $_POST["phone_number"]);

// Enter username
$username = $_POST['username'];

// Password validation and hashing
$password = $_POST["password"];
$password_hash = password_hash($password, PASSWORD_DEFAULT);

// Include AWS SDK for PHP and setup KMS client
require 'vendor/autoload.php';

use Aws\Kms\KmsClient;
use Aws\Credentials\CredentialProvider;

$provider = CredentialProvider::defaultProvider();
$kmsClient = new KmsClient([
    'region'      => 'us-west-2',
    'version'     => 'latest',
    'credentials' => $provider
]);

// Generate a unique user_id
$user_id = uniqid();

// Generate a random IV
$iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));

// Encrypt email
$email = $_POST["email"];
$result = $kmsClient->generateDataKey([
    'KeyId'   => 'arn:aws:kms:us-west-2:576795985210:key/a5326fd9-2492-44b7-bcce-d5f2fcf0dfbd',
    'KeySpec' => 'AES_256'
]);
$encrypted_email = openssl_encrypt($email, 'aes-256-cbc', $result['Plaintext'], OPENSSL_RAW_DATA, $iv);

// Encrypt phone number
$phone_number = $_POST["phone_number"];
$encrypted_phone_number = openssl_encrypt($phone_number, 'aes-256-cbc', $result['Plaintext'], OPENSSL_RAW_DATA, $iv);

// Combine IV and encrypted data
$encrypted_email = base64_encode($iv . $encrypted_email);
$encrypted_phone_number = base64_encode($iv . $encrypted_phone_number);

// SQL query to insert user
$sql = "INSERT INTO users (user_id, username, email, phone_number, password) VALUES (?, ?, ?, ?, ?)";
$stmt = $mysqli->prepare($sql);

if (!$stmt) {
    die("Error with DB: " . $mysqli->error);
}

$stmt->bind_param("sssss", $user_id, $username, $encrypted_email, $encrypted_phone_number, $password_hash);

// Execute the statement and handle possible errors
if (!$stmt->execute()) {
    // If there is a duplicate key error
    if ($mysqli->errno == 1062) {
        die('Error: Duplicate entry');
    } else {
        die('Error: (' . $stmt->errno . ') ' . $stmt->error);
    }
} else {
    if ($stmt->affected_rows === 0) {
        exit('No rows were inserted');
    }

    $stmt->close();

    header("Location: login.html");
    exit;
}
?>

