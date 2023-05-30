<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$mysqli = include __DIR__ . "/database.php";
session_start();

// Retrieve user_id from session
$user_id = $_SESSION['user_id'];
echo "Users ID from session: " . $user_id;

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

// Generate KMS Data Key
$result = $kmsClient->generateDataKey([
    'KeyId'   => 'arn:aws:kms:us-west-2:576795985210:key/a5326fd9-2492-44b7-bcce-d5f2fcf0dfbd',
    'KeySpec' => 'AES_256'
]);

$fields = ['firstname', 'lastname', 'middlename', 'dob', 'gender', 'height', 'weight', 'ethnicity', 'address', 'ssn'];
$encrypted_fields = [];

foreach ($fields as $field) {
    // Generate a new IV for each field
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
    // Encrypt the field
    $encrypted = openssl_encrypt($_POST[$field], 'aes-256-cbc', $result['Plaintext'], OPENSSL_RAW_DATA, $iv);
    // Combine IV and encrypted data
    $encrypted_fields[$field] = base64_encode($iv . $encrypted);
}

// SQL query to insert patient information
$sql = "INSERT INTO patients (user_id, firstname, middlename, lastname, dob, gender, height, weight, ethnicity, address, ssn) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $mysqli->prepare($sql);

if (!$stmt) {
    die("Error with DB: " . $mysqli->error);
}

$stmt->bind_param("sssssssssss", $user_id, $encrypted_fields['firstname'], $encrypted_fields['middlename'], $encrypted_fields['lastname'], $encrypted_fields['dob'], $encrypted_fields['gender'], $encrypted_fields['height'], $encrypted_fields['weight'], $encrypted_fields['ethnicity'], $encrypted_fields['address'], $encrypted_fields['ssn']);

if ($stmt->execute()) {
    if ($stmt->affected_rows === 0) {
        exit('No rows were inserted into patients');
    }

    $stmt->close();

    header("Location: view-info.html");  // Redirect to view case page
    exit;
} else {
    die('Error : (' . $stmt->errno . ') ' . $stmt->error);
}
?>

