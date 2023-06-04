<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Include AWS SDK for PHP and setup KMS client
require 'vendor/autoload.php';

use Aws\Kms\KmsClient;
use Aws\Credentials\CredentialProvider;

$provider = CredentialProvider::defaultProvider();
$kmsClient = new KmsClient([
    'region'      => 'your-aws-region',
    'version'     => 'latest', // Specify the desired version here
    'credentials' => $provider
]);

// Decrypt email
$email = $_SESSION['encrypted_email'];
$result = $kmsClient->decrypt([
    'CiphertextBlob' => $email,
]);

$decrypted_email = $result['Plaintext'];

echo "Decrypted email: " . $decrypted_email;

