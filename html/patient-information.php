<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$mysqli = include __DIR__ . "/database.php";
session_start();

$user_id = $_SESSION['user_id'];

require 'vendor/autoload.php';

use Aws\Kms\KmsClient;
use Aws\Credentials\CredentialProvider;

$provider = CredentialProvider::defaultProvider();
$kmsClient = new KmsClient([
    'region'      => 'us-west-2',
    'version'     => 'latest',
    'credentials' => $provider
]);

$result = $kmsClient->generateDataKey([
    'KeyId'   => 'arn:aws:kms:us-west-2:576795985210:key/a5326fd9-2492-44b7-bcce-d5f2fcf0dfbd',
    'KeySpec' => 'AES_256'
]);

$dataKey = $result['Plaintext'];
$cipherblob = $result['CiphertextBlob'];

//Rules for input validation

$validationRules =[
    'firstname' => FILTER_SANITIZE_STRING,
    'lastname' => FILTER_SANITIZE_STRING,
    'middlename' => FILTER_SANITIZE_STRING,
    'dob' => FILTER_SANITIZE_STRING,
    'gender' => FILTER_SANITIZE_STRING,
    'height' => FILTER_SANITIZE_FLOAT,
    'weight' => FILTER_SANITIZE_FLOAT,
    'ethnicity' => FILTER_SANITIZE_STRING,
    'address' => FILTER_SANITIZE_STRING,
    'ssn' => FILTER_SANITIZE_STRING
];

//validates input 

$filteredInputs = [];

foreach ($validationRules as $field => $filter) {
    $input = $_POST[$field] ?? null; // check to see if each field is filled 

    // Validate and sanitize the input
    $filteredInput = filter_var($input, $filter);

    // stores input in the array 
    $filteredInputs[$field] = $filteredInput;
}

//error handling
$validationErrors = [];

foreach ($filteredInputs as $field => $value) {
    if ($value === false) {
        $validationErrors[] = $field;
    }
}

if (!empty($validationErrors)) {
    // Validation errors occurred, handle them appropriately
    $_SESSION['errors'] = $validationErrors;
    // redirects the user back to the form and display error messages
    header("Location: patient-information.html");
    exit();
} else {
    // Inputs are valid. Proceeds to encryption. 
}






$fields = ['firstname', 'lastname', 'middlename', 'dob', 'gender', 'height', 'weight', 'ethnicity', 'address', 'ssn'];
$encrypted_fields = [];

foreach ($fields as $field) {
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-ctr'));
    
    // Ensure IV is exactly 16 bytes
    if (strlen($iv) != 16) {
        exit("Error: The generated IV for field '$field' is not 16 bytes long.");
    }

    $dataToEncrypt = $_POST[$field];
    $encrypted = openssl_encrypt($dataToEncrypt, 'aes-256-ctr', $dataKey, OPENSSL_RAW_DATA, $iv);
    $encrypted_fields[$field] = base64_encode($iv . $encrypted);
}

$cipherblob_b64 = base64_encode($cipherblob);

$sql = "INSERT INTO patients (user_id, firstname, middlename, lastname, dob, gender, height, weight, ethnicity, address, ssn, cipherblob) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $mysqli->prepare($sql);

if (!$stmt) {
    die("Error with DB: " . $mysqli->error);
}

$stmt->bind_param("ssssssssssss", $user_id, $encrypted_fields['firstname'], $encrypted_fields['middlename'], $encrypted_fields['lastname'], $encrypted_fields['dob'], $encrypted_fields['gender'], $encrypted_fields['height'], $encrypted_fields['weight'], $encrypted_fields['ethnicity'], $encrypted_fields['address'], $encrypted_fields['ssn'], $cipherblob_b64);

if ($stmt->execute()) {
    if ($stmt->affected_rows === 0) {
        exit('No rows were inserted into patients');
    }

    $stmt->close();

    header("Location: view-info.html");
    exit;
} else {
    die('Error : (' . $stmt->errno . ') ' . $stmt->error);
}
?>

