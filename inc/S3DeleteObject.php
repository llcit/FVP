<?php
	require './upload/php-s3-server-master/vendor/autoload.php';
	use Aws\S3\S3Client;
	use Aws\S3\Exception\S3Exception;
	function deleteObject($id) {
		$SETTINGS = parse_ini_file(__DIR__."/settings.ini");
		$expectedBucketName = $SETTINGS['S3_BUCKET_NAME'];
      $config = [
          'region' => 'us-east-1',
          'version' => 'latest'
      ];
      $sdk = new Aws\Sdk($config);
      $client = $sdk->createS3();
		try {
			$video = "videos/$id.mp4";
			$videoResult = $client->deleteObject([
				'Bucket' => $expectedBucketName,
				'Key'	=> $video
			]);

			if ($videoResult['DeleteMarker']){
				echo $video . ' was deleted or does not exist.' . PHP_EOL;
			} else {
				exit('Error: ' . $video . ' was not deleted.' . PHP_EOL);
			}
		}
		catch (S3Exception $e) {
			exit('Error: ' . $e->getAwsErrorMessage() . PHP_EOL);
		}
		try {
			$thumb = "thumbs/$id.jpg"; 
			$thumbResult = $client->deleteObject([
				'Bucket' => $expectedBucketName,
				'Key'	=> $thumb
			]);

			if ($thumbResult['DeleteMarker']){
				echo $thumb . ' was deleted or does not exist.' . PHP_EOL;
			} else {
				exit('Error: ' . $thumb . ' was not deleted.' . PHP_EOL);
			}
		}
		catch (S3Exception $e) {
			exit('Error: ' . $e->getAwsErrorMessage() . PHP_EOL);
		}		
		try {
			$transcript = "transcripts/$id.vtt"; 
		$transcriptResult = $client->deleteObject([
			'Bucket' => $expectedBucketName,
			'Key'	=> $transcript
		]);

		if ($transcriptResult['DeleteMarker']){
			echo $transcript . ' was deleted or does not exist.' . PHP_EOL;
		} else {
			exit('Error: ' . $transcript . ' was not deleted.' . PHP_EOL);
		}
		}
		catch (S3Exception $e) {
			exit('Error: ' . $e->getAwsErrorMessage() . PHP_EOL);
		}	
		deleteObjectFromDB($id);	
	}

?>