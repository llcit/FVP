<?php
require '../upload/php-s3-server-master/vendor/autoload.php';
use Aws\S3\S3Client;

$SETTINGS = parse_ini_file(__DIR__."/../inc/settings.ini");
$serverPrivateKey = $SETTINGS['AWS_SERVER_PRIVATE_KEY'];
$expectedBucketName = $SETTINGS['S3_BUCKET_NAME'];

$type = $_GET['type'];
$id = $_GET['id'];
$ext = $_GET['ext'];
$key =  $type . "s/".$id."." . $ext;
$config = [
    'region' => 'us-east-1',
    'version' => 'latest'
];
$sdk = new Aws\Sdk($config);
$client = $sdk->createS3();
$link = getTempLink($expectedBucketName, $key);
// avoid CORS issues with captions
if ($type == 'transcript' || $type == 'translation') {
    $content = outputContents($link);
    header("Content-Type:text/vtt;charset=utf-8");
    echo($content);
}
else if($type == 'video') {
    $content = outputContents($link);
    header("Content-Type:video/mp4");
    echo($content);
}
else {
    echo($link);  
}

function getTempLink($bucket, $key) {
    global $client;
    $tmpLink = '';
    if (!$client) return null;
    try {
        $cmd = $client->getCommand('GetObject', [
               'Bucket' => $bucket,
               'Key' => $key
       ]);
       $request = $client->createPresignedRequest($cmd, '+60 minutes');
     } catch (S3Exception $e) {
         $tmpLink = new Exception($e->getMessage());
     }
    $tmpLink = (string)$request->getUri();
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