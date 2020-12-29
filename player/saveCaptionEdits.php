<?php
	require '../upload/php-s3-server-master/vendor/autoload.php';
  use Aws\S3\S3Client;
  $SETTINGS = parse_ini_file(__DIR__."/../inc/settings.ini");
  $clientPrivateKey = $SETTINGS['AWS_CLIENT_SECRET_KEY'];
  $serverPublicKey = $SETTINGS['AWS_SERVER_PUBLIC_KEY'];
  $serverPrivateKey = $SETTINGS['AWS_SERVER_PRIVATE_KEY'];
  $expectedBucketName = $SETTINGS['S3_BUCKET_NAME'];

  function getS3Client() {
      global $clientPrivateKey, $serverPrivateKey;
      return S3Client::factory(array(
          'key' => $serverPrivateKey,
          'secret' => $clientPrivateKey
      ));
  }
  function writeVTTFile($pid,$data,$language) {
    global $expectedBucketName;
    $fileContent = "WEBVTT\r\nKind: captions\r\nLanguage: ".$language."\r\n\r\n";
    foreach($data as $row) {
      $start = time_format($row->start);
      $end = time_format($row->start);
      $text = $row->text;
      $fileContent .=  $start . " --> " . $end ."\r\n";
      $fileContent .= $row->text  ."\r\n\r\n";         
    }
    $captionType = ($language == 'en') ? 'translation' : 'transcipt';
    $key = "$captionType/$pid.vtt";

    $client = getS3Client();
    $command = $client->getCommand('PutObject', array(
            'Bucket' => $expectedBucketName,
            'Key'    => "$key",
            'Body'   => "$fileContent"
    ));
    $result = $command->getResult();
    $response = $command->getResponse();
    $code = $response->getStatusCode();
    $success = ($code === 200) ? true : false ;
    return $success;
  }
  function time_format($rawTime) {
    if ($rawTime) {
        // always 0 microseconds
        $microseconds = '000';
        return $rawTime . '.' . $microseconds;
    }
  }     
?>