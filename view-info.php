<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$mysqli = include __DIR__ . "/database.php";
session_start();

// Retrieve user_id from session
$user_id = $_SESSION['user_id'];

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

// SQL query to get patient information
$sql = "SELECT * FROM patients WHERE user_id = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("s", $user_id);
$stmt->execute();

$result = $stmt->get_result();
$data = $result->fetch_assoc();

// Fetch the user data
$dataKey = $kmsClient->generateDataKey([
    'KeyId'   => 'arn:aws:kms:us-west-2:576795985210:key/a5326fd9-2492-44b7-bcce-d5f2fcf0dfbd',
    'KeySpec' => 'AES_256'
]);

$plaintextKey = $dataKey['Plaintext'];

$keysAndFields = ['firstname', 'lastname', 'middlename', 'dob', 'gender', 'height', 'weight', 'ethnicity', 'address', 'ssn'];
$decryptedData = [];

foreach ($keysAndFields as $key) {
    // Assuming the data is stored in the format `iv + encrypted data` and the iv is 16 bytes long
    $iv = substr($data[$key], 0, 16);
    $encryptedData = substr($data[$key], 16);

    // Pad the iv with null bytes if it's shorter than 16 bytes
    $iv = str_pad($iv, 16, "\0");

    // Decrypt the data
    $decryptedData[$key] = openssl_decrypt($encryptedData, 'aes-256-cbc', $plaintextKey, OPENSSL_RAW_DATA, $iv);
}

// Output the data as an HTML table
echo "<table border='1'>";
echo "<tr>";
foreach ($keysAndFields as $key) {
    echo "<th>" . ucfirst($key) . "</th>";
}
echo "</tr>";
echo "<tr>";
foreach ($keysAndFields as $key) {
    echo "<td>" . htmlentities($decryptedData[$key]) . "</td>";
}
echo "</tr>";
echo "</table>";

?>

