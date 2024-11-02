<?php
require_once "vendor/autoload.php";

use Aws\Credentials\CredentialProvider;
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;

try {
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

    $s3bucket = "learnlab-65070242";
    $s3bucket_post = $s3bucket;
    $s3bucket_submit = $s3bucket;
    $s3bucket_avatar = $s3bucket;
    $s3bucket_banner =  $s3bucket;

    $s3_folder = "courses/";
    $s3_post_folder = "courses/";
    $s3_submit_folder = "courses/";
    $s3_avatar_folder = "avatar/";
    $s3_banner_folder = "banner/";

} catch (S3Exception $e) {
    echo jsonResponse("500", $e->getMessage());
}

function getS3PreSignedUrl($client,string $bucket,string $key, $expire = new DateTime("+10 minutes")){
    $command = $client->getCommand('GetObject', [
        'Bucket' => $bucket,
        "Key" => $key
    ]);
    $reqPreSignedUrl = $client->createPresignedRequest($command, $expire);
    $presignedUrl = (string)$reqPreSignedUrl->getUri();
    return $presignedUrl ;
}
