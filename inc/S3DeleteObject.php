<?php
	require '../upload/php-s3-server-master/vendor/autoload.php';
	use Aws\S3\S3Client;
	use Aws\S3\Exception\S3Exception;
	function deleteVideo($id) {
		$SETTINGS = parse_ini_file(__DIR__."/../inc/settings.ini");
		$clientPrivateKey = $SETTINGS['AWS_CLIENT_SECRET_KEY'];
		$serverPrivateKey = $SETTINGS['AWS_SERVER_PRIVATE_KEY'];
		$expectedBucketName = $SETTINGS['S3_BUCKET_NAME'];
		$client = getS3Client($clientPrivateKey, $serverPrivateKey);
		try {
			$video = 'videos/$id.mp4';
	    $videoResult = $s3->deleteObject([
	        'Bucket' => $bucket,
	        'Key'    => $video
	    ]);

	    if ($videoResult['DeleteMarker']){
	        echo $keyname . ' was deleted or does not exist.' . PHP_EOL;
	    } else {
	        exit('Error: ' . $video . ' was not deleted.' . PHP_EOL);
	    }
		}
		catch (S3Exception $e) {
		    exit('Error: ' . $e->getAwsErrorMessage() . PHP_EOL);
		}
		try {
			$thumb = 'thumbs/$id.jpg'; 
	    $thumbResult = $s3->deleteObject([
	        'Bucket' => $bucket,
	        'Key'    => $thumb
	    ]);

	    if ($thumbResult['DeleteMarker']){
	        echo $keyname . ' was deleted or does not exist.' . PHP_EOL;
	    } else {
	        exit('Error: ' . $thumb . ' was not deleted.' . PHP_EOL);
	    }
		}
		catch (S3Exception $e) {
		    exit('Error: ' . $e->getAwsErrorMessage() . PHP_EOL);
		}		
		try {
			$transcript = 'transcripts/$id.vtt'; 
	    $transcriptResult = $s3->deleteObject([
	        'Bucket' => $bucket,
	        'Key'    => $transcript
	    ]);

	    if ($transcriptResult['DeleteMarker']){
	        echo $keyname . ' was deleted or does not exist.' . PHP_EOL;
	    } else {
	        exit('Error: ' . $transcript . ' was not deleted.' . PHP_EOL);
	    }
		}
		catch (S3Exception $e) {
		    exit('Error: ' . $e->getAwsErrorMessage() . PHP_EOL);
		}		
	}
	function getS3Client($clientPrivateKey, $serverPrivateKey) {
    return S3Client::factory(array(
        'key' => $serverPrivateKey,
        'secret' => $clientPrivateKey
    ));
}
?>