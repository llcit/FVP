<?php
	
	require './vendor/autoload.php';
	use Aws\S3\S3Client;

	$SETTINGS = parse_ini_file(__DIR__."/../../inc/settings.ini");
	$expectedBucketName = $SETTINGS['S3_BUCKET_NAME'];

  include "../../inc/db_pdo.php";
  include "../../inc/sqlFunctions.php";
  include "../../inc/S3LinkGen.php";
	$pres = getPid($_GET['access_code']);
	$pid = $pres->id;
	$key =  "thumbs/".$pid.".jpg";

	$config = [
	    'region' => 'us-east-1',
	    'version' => 'latest'
	];
	$sdk = new Aws\Sdk($config);
	$client = $sdk->createS3();

	$link = getTempLink($expectedBucketName, $key);
	echo($link);

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
?>