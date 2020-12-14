<?php

require './php-s3-server-master/vendor/autoload.php';
use Aws\S3\S3Client;


$serverPublicKey = $_SERVER['PARAM1'];
$serverPrivateKey = $_SERVER['PARAM2'];

var_dump($_SERVER);
$SETTINGS = parse_ini_file(__DIR__."/../inc/settings.ini");

// Instantiate the S3 client with your AWS credentials
$s3 = S3Client::factory(array(

'key' => 'AKIA5GGQGDXJ7N57DNHH',
'secret' => '23WbtiAgyy4zzEZM+pgdfOin+o8B3V3ngTCFMuar',
));


$bucket = 'flagship-video-project';

// Use the high-level iterators (returns ALL of your objects).
$objects = $s3->getIterator('ListObjects', array('Bucket' => $bucket));

echo "Keys retrieved!\n";
foreach ($objects as $object) {
    echo $object['Key'] . "\n";
}

// Use the plain API (returns ONLY up to 1000 of your objects).
$result = $s3->listObjects(array('Bucket' => $bucket));

echo "Keys retrieved!\n";
foreach ($result['Contents'] as $object) {
    echo $object['Key'] . "\n";
}

?>
