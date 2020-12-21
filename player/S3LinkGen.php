<?php
require '../upload/php-s3-server-master/vendor/autoload.php';
use Aws\S3\S3Client;

$SETTINGS = parse_ini_file(__DIR__."/../inc/settings.ini");
$clientPrivateKey = $SETTINGS['AWS_CLIENT_SECRET_KEY'];
$serverPublicKey = $SETTINGS['AWS_SERVER_PUBLIC_KEY'];
$serverPrivateKey = $SETTINGS['AWS_SERVER_PRIVATE_KEY'];
$expectedBucketName = $SETTINGS['S3_BUCKET_NAME'];

$id = $_GET['id'];
$ext = $_GET['ext'];
$key =  "videos/".$id."." . $ext;
$link = getTempLink($expectedBucketName, $key);
echo($link);

function getS3Client() {
    global $clientPrivateKey, $serverPrivateKey;
    return S3Client::factory(array(
        'key' => $serverPrivateKey,
        'secret' => $clientPrivateKey
    ));
}
function getTempLink($bucket, $key) {
    $client = getS3Client();
    if (!$client) return null;
    try {
        $url = "{$bucket}/{$key}";
        $request = $client->get($url);
        $tmpLink = $client->createPresignedUrl($request, '+60 minutes');
    } catch (S3Exception $e) {
        $tmpLink =  new Exception($e->getMessage());
    }
    return $tmpLink;
}