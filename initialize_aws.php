<?php
require_once "vendor/autoload.php";
use Aws\Credentials\CredentialProvider;
use Aws\S3\S3Client;

$profile = "default";
$path = __DIR__ . "/aws_credentials.ini";

// If Use .ini File
$provider = CredentialProvider::ini($profile, $path);

// If Use EC2 With Set IAM Role
/*$provider = CredentialProvider::instanceProfile([
    'retries' => 0
]);*/

$provider = CredentialProvider::memoize($provider);

// Next Step Create S3 Client

$s3client = new S3Client([
    "region" => "us-east-1",
    "version" => "latest",
    "credentials" => $provider,
]);

$s3bucket = "oardisodb";
