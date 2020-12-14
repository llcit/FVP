<?php

require './vendor/autoload.php';
use Aws\S3\S3Client;

$SETTINGS = parse_ini_file(__DIR__."/../../inc/settings.ini");

$clientPrivateKey = $SETTINGS['AWS_CLIENT_SECRET_KEY'];
// These two keys are only needed if the delete file feature is enabled
// or if you are, for example, confirming the file size in a successEndpoint
// handler via S3's SDK, as we are doing in this example.
$serverPublicKey = $SETTINGS['AWS_SERVER_PUBLIC_KEY'];
$serverPrivateKey = $SETTINGS['AWS_SERVER_PRIVATE_KEY'];


function getS3Client() {
    global $clientPrivateKey, $serverPrivateKey;

    return S3Client::factory(array(
        'key' => $serverPrivateKey,
        'secret' => $clientPrivateKey
    ));
}

function getObjectSize($bucket, $key) {
try {
        echo("BUCKET: " .$bucket . "\n\n");
        echo("KEY: " .$key . "\n\n");
        $objInfo = getS3Client()->headObject(array(
            'Bucket' => $bucket,
            'Key' => $key
        ));
} catch (Exception $e) {
  echo json_encode(array("error" => "$e"));
}
    return $objInfo['ContentLength'];
}

$os = getObjectSize('flagship-video-project','732becc8-3a02-4c16-9081-dae31c373187.jpg');
echo("Here's the goods:  $os\n\n");
