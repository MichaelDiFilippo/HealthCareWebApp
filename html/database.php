<?php

require 'vendor/autoload.php';  // Make sure to include the AWS SDK for PHP

use Aws\SecretsManager\SecretsManagerClient;
use Aws\Exception\AwsException;

// Create a Secrets Manager client
$client = new SecretsManagerClient([
    'version' => 'latest',
    'region'  => 'us-west-2'  // Your AWS region
]);

$secretName = 'arn:aws:secretsmanager:us-west-2:576795985210:secret:HealthSecrets-vd0RDa';  // The name of your secret

try {
    $result = $client->getSecretValue([
        'SecretId' => $secretName
    ]);

} catch (AwsException $e) {
    echo $e->getAwsErrorMessage();
    die();
}

if (isset($result['SecretString'])) {
    $secret = $result['SecretString'];
} else {
    $secret = base64_decode($result['SecretBinary']);
}

$credentials = json_decode($secret, true);

$host = $credentials['host'];
$db = $credentials['dbname'];
$user = $credentials['username'];
$pass = $credentials['password'];
$port = 3306;

// Create connection
$mysqli = mysqli_connect($host, $user, $pass, $db, $port);

// Check connection
if (!$mysqli) {
    die("Connection failed: " . mysqli_connect_error());
}
echo "Connected successfully";
return $mysqli;
?>

