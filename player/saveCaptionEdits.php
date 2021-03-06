<?php
	require '../upload/php-s3-server-master/vendor/autoload.php';
  use Aws\S3\S3Client;
  use Aws\S3\Exception\S3Exception;
  $SETTINGS = parse_ini_file(__DIR__."/../inc/settings.ini");
  $expectedBucketName = $SETTINGS['S3_BUCKET_NAME'];

  function writeVTTFile($pid,$data,$language) {
    global $expectedBucketName;
    $fileContent = "WEBVTT\r\nKind: captions\r\nLanguage: ".$language."\r\n\r\n";
    foreach($data as $row) {
      $start = time_format($row->start);
      $end = time_format($row->end);
      $text = $row->text;
      $fileContent .=  $start . " --> " . $end ."\r\n";
      $fileContent .= $row->text  ."\r\n\r\n";         
    }
    $captionType = ($language == 'en') ? 'translation' : 'transcript';
    $key = $captionType."s/".$pid.".vtt";
    $config = [
        'region' => 'us-east-1',
        'version' => 'latest'
    ];
    $sdk = new Aws\Sdk($config);
    $client = $sdk->createS3();
    $client->registerStreamWrapper();
    try { 
      $stream = fopen("s3://$expectedBucketName/$key", 'w');
      fwrite($stream, $fileContent);
      fclose($stream);
      return true;
    }catch (S3Exception $e) {
      echo $e->getMessage();
    }
  }
  function time_format($rawTime) {
    if ($rawTime) {
        $microseconds = '000';
        return $rawTime . '.' . $microseconds;
    }
  }     
?>