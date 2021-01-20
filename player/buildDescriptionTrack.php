<?php
/*
rip from xls

1. Clean initial 0s:		\r(0)?\: 			 -> 	\r00:
2. Clean hyphens:			(\s*)?\-(\s*+\-*)?  --> 	 -
3. Clean medial 0s			([^\d])(\d)([^\d])  -->  	\1 \0\2\3
4. Remove initial space		\r 0				-->		\r0
5.         (\d*)\:(\d*)\-(\d*)\:(\d*)   --->  \r00:\1:\2.000 --> 00:\3:\4.999\r
*/
	require '../upload/php-s3-server-master/vendor/autoload.php';
	use Aws\S3\S3Client;

	$SETTINGS = parse_ini_file(__DIR__."/../inc/settings.ini");
	$expectedBucketName = $SETTINGS['S3_BUCKET_NAME']; 
	$config = [
	    'region' => 'us-east-1',
	    'version' => 'latest'
	];
	$sdk = new Aws\Sdk($config);
	$client = $sdk->createS3();
	$client = getS3Client();
	$client->registerStreamWrapper();
	$id = $_GET['v'];
	$type = 'annotation';
	$ext = 'vtt';

	$annotationFilesOnS3 = explode(',',$_GET['a']);
	// list of requested tracks from post 
	$tracks = explode(',',$_GET['t']);
	
	// all lines from all files
	$parsedLines = [];
	
	// merge all lines from specified tracks into one big array
	foreach ($annotationFilesOnS3 as $annotationFile) {
		$annotationType = strtolower($annotationFile);
		if (in_array($annotationType,$tracks)) {
			$key =  $type . "s/".$annotationType."/".$id."." . $ext;
			if ($stream = fopen("s3://$expectedBucketName/$key", 'r')) {
			    // While the stream is still open
			    while (!feof($stream)) {
			        // Read 1024 bytes from the stream
			        $contents = fread($stream, 1024);
			    }
			    // Be sure to close the stream resource when you're done with it
			    fclose($stream);
			}
			$lines = preg_split("/\\n/", $contents);
			foreach ($lines as $line) {
				if ($line == "" || $line == 'WEBVTT' || preg_match("/^(Kind|Language):/",$line)) {
					$parsedLine = [];
				}
				else {
					// time codes
					if ($line[0] == '0') {
						// get the start time
						preg_match("/(\d{2}\:\d{2}\:\d{2}).*/",$line, $matches);
						// reduce time signatures to integers for sorting
						$start = preg_replace("/\:/", "",$matches[1]);
						// add start for sorting
						$parsedLine['start'] = $start;
						// add time reference
						$parsedLine['timeCodes'] = $line;
						// we need this to theme categories in the player descriptions
						$parsedLine['category'] = $annotationType;
					}
					// transcriptions
					else {
						// add the trancsribed utterance
						$parsedLine['text'] = $line;
						// we need this to theme categories in the player descriptions
						$parsedLine['category'] = $annotationType;
						array_push($parsedLines,$parsedLine);
					}
				}
			}
		}
	}
	
	// sort by start time-- important.  This is the only way to keep the correct time-sequenced display!
	usort($parsedLines, 'sortByStart');
	
	// build the text of the merged and sorted vtt entries
	$mergedTrack = "WEBVTT\r\n\r\n";
	foreach($parsedLines as $parsedLine) {
		$mergedTrack .= $parsedLine['timeCodes'] . "\r\n[" . $parsedLine['category']. "] " . $parsedLine['text']. "\r\n\r\n";
	}
	
	// print out the merged VTT track
	header("Content-Type:text/vtt;charset=utf-8");
	print($mergedTrack);
	
	function sortByStart($a, $b) {
	    return $a['start'] - $b['start'];
	}
	
?>