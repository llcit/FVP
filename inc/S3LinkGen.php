<?php
require '../upload/php-s3-server-master/vendor/autoload.php';
use Aws\S3\S3Client;

$SETTINGS = parse_ini_file(__DIR__."/../inc/settings.ini");
$clientPrivateKey = $SETTINGS['AWS_CLIENT_SECRET_KEY'];
$serverPrivateKey = $SETTINGS['AWS_SERVER_PRIVATE_KEY'];
$expectedBucketName = $SETTINGS['S3_BUCKET_NAME'];

$type = $_GET['type'];
$id = $_GET['id'];
$ext = $_GET['ext'];
$key =  $type . "s/".$id."." . $ext;
$client = getS3Client();
$link = getTempLink($expectedBucketName, $key);
// avoid CORS issues with captions
if ($type == 'transcript' || $type == 'translation') {
    $content = outputContents($link);
    echo($content);
}
else {
    echo($link);  
}

function getS3Client() {
    global $clientPrivateKey, $serverPrivateKey;
    return S3Client::factory(array(
        'key' => $serverPrivateKey,
        'secret' => $clientPrivateKey
    ));
}
function getTempLink($bucket, $key) {
    global $client;
    if (!$client) return null;
    try {
        $url = "{$bucket}/{$key}";
        $request = $client->get($url);
        $tmpLink = $client->createPresignedUrl($request, '+60 minutes');
    } catch (S3Exception $e) {
        $tmpLink = new Exception($e->getMessage());
    }
    return $tmpLink;
}
function outputContents($tmpLink) {
    global $client;
    $client->registerStreamWrapper();
    $contents = '';
    if ($stream = fopen("$tmpLink", 'r')) {
        while (!feof($stream)) {
            $contents .= fread($stream, 1024);
        }
        fclose($stream);
    }
    return $contents;
}