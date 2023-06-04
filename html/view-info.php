<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$mysqli = include __DIR__ . "/database.php";

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

$cipherblob = base64_decode($data['cipherblob']);

$dataKeyResult = $kmsClient->decrypt([
    'CiphertextBlob' => $cipherblob,
]);

$dataKey = $dataKeyResult['Plaintext'];

$keysAndFields = ['firstname', 'lastname', 'middlename', 'dob', 'gender', 'height', 'weight', 'ethnicity', 'address', 'ssn'];
$decryptedData = [];

foreach ($keysAndFields as $key) {
    $decodedData = base64_decode($data[$key]);

    // DEBUG: Check the integrity of the base64 decoding.
    if ($decodedData === false) {
        echo "Error: Base64 decoding failed for key $key<br/>";
        continue;
    }

    // Extract the IV and encrypted data
    $iv = substr($decodedData, 0, 16);
    $encryptedData = substr($decodedData, 16);

    if(strlen($iv) != 16) {
        echo "Error: The IV for field '$key' was not 16 bytes long.<br/>";
        continue;
    }

    // Decrypt the data
    $decrypted = openssl_decrypt($encryptedData, 'aes-256-ctr', $dataKey, OPENSSL_RAW_DATA, $iv);
    $decryptedData[$key] = $decrypted;

    // Check for OpenSSL errors
    $error = openssl_error_string();
    if ($error !== false) {
        echo "OpenSSL Error for key $key: $error<br/>";
    }
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
    // Check if decryptedData[key] is not null
    $decryptedValue = isset($decryptedData[$key]) ? htmlentities($decryptedData[$key]) : '';
    echo "<td>" . $decryptedValue . "</td>";
}
echo "</tr>";
echo "</table>";
?>

