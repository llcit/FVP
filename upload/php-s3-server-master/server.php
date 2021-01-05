<?php
/* TO DO 12/20
1. Finish FFMPeg progress
2. Generate and upload thumb in ffmpeg 205x117
3. Fix language and extension
4. clean up tmp files
5. Backfill durations
*/
    // blow open memory limit
    ini_set('memory_limit', '-1');
    require './vendor/autoload.php';
    use Aws\S3\S3Client;
    use Google\Cloud\Storage\StorageClient;
    use Google\Cloud\Speech\V1\SpeechClient;
    use Google\Cloud\Speech\V1\RecognitionAudio;
    use Google\Cloud\Speech\V1\RecognitionConfig;
    use Google\Cloud\Speech\V1\RecognitionConfig\AudioEncoding;

    $SETTINGS = parse_ini_file(__DIR__."/../../inc/settings.ini");

    $clientPrivateKey = $SETTINGS['AWS_CLIENT_SECRET_KEY'];
    $serverPublicKey = $SETTINGS['AWS_SERVER_PUBLIC_KEY'];
    $serverPrivateKey = $SETTINGS['AWS_SERVER_PRIVATE_KEY'];
    $expectedBucketName = $SETTINGS['S3_BUCKET_NAME'];
    $expectedHostName = $SETTINGS['S3_HOST_NAME']; // v4-only
    $expectedMaxSize = (isset($SETTINGS['S3_MAX_FILE_SIZE']) ? $SETTINGS['S3_MAX_FILE_SIZE'] : null);
    $method = getRequestMethod();

    if ($method == 'OPTIONS') {
        handlePreflight();
    }
    else if ($method == 'DELETE') {
        handleCorsRequest(); // only needed in a CORS environment
        deleteObject();
    }
    else if ($method == 'POST') {
        handleCorsRequest();
        if (isset($_REQUEST["success"])) {
            include_once("../../inc/db_pdo.php"); 
            include_once("../../inc/sqlFunctions.php");
            preg_match("/(.*)\.(mov|mp4|m4a)/",$_REQUEST['key'],$matches);
            $file_name = $matches[1];
            $video_extension = $matches[2];
            $pid = ($_REQUEST['pid'] > 0) ? $_REQUEST['pid'] : registerVideo($_REQUEST['user_id'],$_REQUEST['event_id'],$_REQUEST['presentation_type'],$video_extension,$_REQUEST['access_code']);
            echo($pid);
            if ($pid) {
                $tmpLink = verifyFileInS3($_REQUEST['key']);
                $language = $_REQUEST['language'];
                $transcribeResult = generateTranscript($tmpLink,$pid,$language);
                $confirmation = confirmUpload($pid,$transcribeResult['duration'],$transcribeResult['success'],$tmpLink,$video_extension);
                renameFile($_REQUEST['key'],$pid,$video_extension);
            }
            
        }
        else {
            signRequest();
        }
    }
    function renameFile($key,$pid,$extension) {
        global $expectedBucketName;
        $client=getS3Client();
        // Ugh!  Only way to rename is to copy and delete-- gross!
        // FVP TO DO: kill once filenameParam in fineuploade client is working 
        $newKey = "videos/$pid".".".$extension;
        $client->copyObject([
            'Bucket'     => $expectedBucketName,
            'Key'        => $newKey,
            'CopySource' => "$expectedBucketName/$key",
        ]);
        $client->deleteObject([
            'Bucket' =>  $expectedBucketName,
            'Key' => $key
        ]);
        return $newKey;
    }
    function verifyFileInS3() {
        global $expectedMaxSize;
        $bucket = $_REQUEST["bucket"];
        $key = $_REQUEST["key"];
        if (isset($expectedMaxSize) && getObjectSize($bucket, $key) > $expectedMaxSize) {
            header("HTTP/1.0 500 Internal Server Error");
            deleteObject();
            echo json_encode(array("error" => "File is too big!", "preventRetry" => true));
        }
        else {
            $link = getTempLink($bucket, $key);
            return $link;
        }
    }
    function confirmUpload($pid,$duration,$transcript_success,$link,$extension) {
        $data = ['id'=>$pid,'transcript_raw' => $transcript_success,'duration'=>$duration,'extension'=>$extension];
        finalizePresentation($data);
        $response = array("tempLink" => $link);
        echo json_encode($response);
        return $response;
    }
    function transcribe_Watson($audioFile,$language) {
        global $SETTINGS;
        $audio_extension = $SETTINGS['tmp_audio_extension'];
        $models = [
            'Arabic' => 'ar-AR_BroadbandModel',
            'Chinese' => 'zh-CN_BroadbandModel',
            'English' => 'en-US_BroadbandModel',
            'Korean' => 'ko-KR_BroadbandModel',
            'Portuguese' => 'pt-BR_BroadbandModel'
        ];
        $url = $SETTINGS['WATSON_SPEECH_URL']."/".$models[$language].
               "/recognize?timestamps=true";
        echo("\n\nWatson URL: $url\n\n");
        $username = $SETTINGS['WATSON_SPEECH_USER'];
        $password = $SETTINGS['WATSON_SPEECH_PWD'];
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        $headers = ["Content-Type: audio/$audio_extension"];
        curl_setopt($ch, CURLOPT_POST,1);
        curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS,file_get_contents("./tmpAudio/" . $audioFile));
        $response = curl_exec($ch);
        $status_code = curl_getinfo($ch,CURLINFO_HTTP_CODE);
        if (!in_array($status_code, [200,201])) {
            throw new Exception("GOT $status_code FROM $url:\n$response");
        }
        if ($response) {
            echo ("done!" . "<br>");
            $captionFile = preg_replace("/\.$audio_extension/",".vtt", $audioFile);
            return ['file'=> $captionFile, 'response'=>$response];
        } 
        else {
            return false;
        }
    }  
    function writeVTTFile($captionFile,$data,$language) {
        global $expectedBucketName,$pid;
        $languages = [
            'Arabic' => 'ar',
            'Chinese' => 'zh',
            'English' => 'en',
            'Korean' => 'ko',
            'Portuguese' => 'pt'
        ];
        $fileContent = "WEBVTT\r\nKind: captions\r\nLanguage: ".$languages[$language]."\r\n\r\n";
        $textType = 'captions';
        $raw_transcript = json_decode($data);
        foreach($raw_transcript->results as $result) {
            $start = time_format($result->alternatives[0]->timestamps[0][1]);
            $end = time_format($result->alternatives[0]->timestamps[count($result->alternatives[0]->timestamps)-1][2]);
            if ($textType == 'captions') {
                $fileContent .=  $start . " --> " . $end ."\r\n";
                $fileContent .= $result->alternatives[0]->transcript  ."\r\n\r\n";
            }
            else if ($textType == 'paragraph') {
                $fileContent .= $result->alternatives[0]->transcript ." ";
            }
            
        }
        $client = getS3Client();
        $command = $client->getCommand('PutObject', array(
                'Bucket' => $expectedBucketName,
                'Key'    => "transcripts/$pid.vtt",
                'Body'   => "$fileContent"
        ));
        $result = $command->getResult();
        $response = $command->getResponse();
        $code = $response->getStatusCode();
        $success = ($code === 200) ? true : false ;
        return $success;
    } 

    function transcribe_Google($audioFile,$language) {
        global $expectedBucketName,$pid;
        $source = "./tmpAudio/$audioFile";
        $googleBucketName = 'flagship-video';
        $objectName = "$audioFile";
        $storage = new StorageClient();
        $file = fopen($source, 'r');
        $bucket = $storage->bucket($googleBucketName);
        $object = $bucket->upload($file, [
            'name' => $objectName
        ]);
        $languages = [
            'Russian' => 'ru-RU'
        ];
        $encoding = AudioEncoding::FLAC;
        $sampleRateHertz = 48000;
        $languageCode = $languages[$language];
        if (!extension_loaded('grpc')) {
            throw new \Exception('Install the grpc extension (pecl install grpc)');
        }
        $gcsURI = "gs://$googleBucketName/$audioFile";
        $audio = (new RecognitionAudio())
            ->setUri($gcsURI);
        // set config
        $config = (new RecognitionConfig())
            ->setEncoding($encoding)
            ->setSampleRateHertz($sampleRateHertz)
            ->setLanguageCode($languageCode)
            ->setEnableWordTimeOffsets(1);
        // create the speech client
        $client = new SpeechClient();
        // create the asyncronous recognize operation
        $operation = $client->longRunningRecognize($config, $audio);
        $operation->pollUntilComplete();
        if ($operation->operationSucceeded()) {
            $response = $operation->getResult();
            $fileContent = "WEBVTT\r\nKind: captions\r\nLanguage: ru\r\n\r\n";
            $startNewLine = true;
            foreach ($response->getResults() as $result) {
                $alternatives = $result->getAlternatives();
                $mostLikely = $alternatives[0];
                if ($mostLikely) {
                    foreach ($mostLikely->getWords() as $wordInfo) {
                        $totalWordCount++;
                        if ($startNewLine) {
                            $startTime = $wordInfo->getStartTime();
                            $start = time_format($startTime->serializeToJsonString());
                            $wordCount = 0;
                            $caption = '';
                            $space = '';
                        }
                        $caption .=  $space . $wordInfo->getWord();
                        $space = ' ';
                        if ($wordCount<=7 && $totalWordCount != count($mostLikely->getWords())) {
                            $startNewLine = false;
                            $wordCount++;
                        }
                        else {
                            $count++;
                            $endTime = $wordInfo->getEndTime();
                            $end = time_format($endTime->serializeToJsonString());
                            $fileContent .= $start . " --> " . $end ."\r\n";
                            $fileContent .= $caption ."\r\n\r\n";
                            $startNewLine = true;
                        }
                    }
                }
            }
        $client = getS3Client();
        $command = $client->getCommand('PutObject', array(
                'Bucket' => $expectedBucketName,
                'Key'    => "transcripts/$pid.vtt",
                'Body'   => "$fileContent"
        ));
        $AWSResult = $command->getResult();
        $AWSResponse = $command->getResponse();
        $code = $AWSResponse->getStatusCode();
        $success = ($code === 200) ? true : false ;
        return $success;

        } else {
            print_r($operation->getError());
        }

        $client->close();
    }

    function time_format($rawTime) {
        if ($rawTime) {
            list($seconds, $ms) = preg_split("/\./",$rawTime);
            // always 0 microseconds
            $microseconds = '000';
            return gmdate("H:i:s", $seconds) . '.' . $microseconds;
        }
    }
    function generateTranscript($tmpLink,$pid,$language) {
        global $SETTINGS,$expectedBucketName;
        $audio_extension = $SETTINGS['tmp_audio_extension'];
        echo ("\n\nRIP pid: $pid\n\n");
        $output_dir = './tmpAudio/';
        $ffmpeg = FFMpeg\FFMpeg::create([
            'ffmpeg.binaries'  => '/usr/bin/ffmpeg', // the path to the FFMpeg binary
            'ffprobe.binaries' => '/usr/bin/ffprobe', // the path to the FFProbe binary
            'timeout' => 3600, // the timeout for the underlying process
            'ffmpeg.threads'   => 1   // the number of threads that FFMpeg should use
        ]);
        $ffmpeg->getFFMpegDriver()->listen(new \Alchemy\BinaryDriver\Listeners\DebugListener());
        $ffmpeg->getFFMpegDriver()->on('debug', function ($message) {       
            echo "MSG: " . $message."\n";
        }); 
        $video = $ffmpeg->open($tmpLink);
        $frame = $video->frame(FFMpeg\Coordinate\TimeCode::fromSeconds(1))->save("./tmpThumbs/".$pid."_large.jpg");
        // use GD to resize
        $original = imagecreatefromjpeg("./tmpThumbs/".$pid."_large.jpg");   
        $thumb = imagescale($original,205,117); 
        // save resized thumb  
        imagejpeg($thumb,"./tmpThumbs/$pid.jpg");  
        $client = getS3Client();
        $command = $client->getCommand('PutObject', array(
                'Bucket' => $expectedBucketName,
                'Key'    => "thumbs/$pid.jpg",
                'SourceFile'   => "./tmpThumbs/$pid.jpg"
        ));
        $result = $command->getResult();
        $response = $command->getResponse();
        $code = $response->getStatusCode();
        $success = ($code === 200) ? true : false ;
        if ($audio_extension == 'mp3') {
            $output_format = new FFMpeg\Format\Audio\Mp3(); 
            $output_format->setAudioCodec("libmp3lame");
        }
        if ($audio_extension == 'flac') {
            $output_format = new FFMpeg\Format\Audio\Flac();  
            $output_format->setAudioChannels(1);
            $output_format->setAudioKiloBitrate(256);
        }
        $output_format->on('progress', function ($video, $format, $percentage) use($pid) {
            file_put_contents('./progress/'. $pid . '.txt', $percentage);
        }); 

        $saveFile = addslashes($output_dir . $pid . "." . $audio_extension);
        $video->save($output_format, $saveFile);
        // onprogress stops before 100, so update for progress bar
        file_put_contents('./progress/'. $pid . '.txt', '100'); 
        // clean up tmp files
        unlink("./tmpThumbs/$pid.jpg");
        unlink("./tmpThumbs/".$pid."_large.jpg");
        $audioFile = $pid . "." . $audio_extension;
        if ($language != 'Russian') {
            $response = transcribe_Watson($audioFile,$language);
        }
        else {
            $response = transcribe_Google($audioFile,$language);
        }
        $transcribeSuccess = writeVTTFile($response['file'],$response['response'],$language);
        $ffprobe = FFMpeg\FFProbe::create();
        $ffprobe = FFMpeg\FFProbe::create();
        $duration =$ffprobe
            ->format($output_dir . $pid . "." . $audio_extension) // extracts file informations
            ->get('duration'); 
        // clean up tmp files
        unlink("./tmpAudio/$audioFile");
        unlink("./tmpThumbs/$pid.jpg");
        unlink("./tmpThumbs/".$pid."_large.jpg");
        return ['duration' => $duration, 'success' => $transcribeSuccess];
    } 
    function getRequestMethod() {
        global $HTTP_RAW_POST_DATA;
        if(isset($HTTP_RAW_POST_DATA)) {
            parse_str($HTTP_RAW_POST_DATA, $_POST);
        }
        if (isset($_REQUEST['_method'])) {
            return $_REQUEST['_method'];
        }
        return $_SERVER['REQUEST_METHOD'];
    }
    function handleCorsRequest() {
        // If you are relying on CORS, you will need to adjust the allowed domain here.
        header('Access-Control-Allow-Origin: http://fineuploader.com');
    }
    function handlePreflight() {
        handleCorsRequest();
        header('Access-Control-Allow-Methods: POST');
        header('Access-Control-Allow-Headers: Content-Type');
    }
    function getS3Client() {
        global $clientPrivateKey, $serverPrivateKey;
        return S3Client::factory(array(
            'key' => $serverPrivateKey,
            'secret' => $clientPrivateKey
        ));
    }
    function deleteObject() {
        getS3Client()->deleteObject(array(
            'Bucket' => $_REQUEST['bucket'],
            'Key' => $_REQUEST['key']
        ));
    }

    function signRequest() {
        header('Content-Type: application/json');
        $responseBody = file_get_contents('php://input');
        $contentAsObject = json_decode($responseBody, true);
        $jsonContent = json_encode($contentAsObject);

        if (!empty($contentAsObject["headers"])) {
            signRestRequest($contentAsObject["headers"]);
        }
        else {
            signPolicy($jsonContent);
        }
    }

    function signRestRequest($headersStr) {
        $version = isset($_REQUEST["v4"]) ? 4 : 2;
        if (isValidRestRequest($headersStr, $version)) {
            if ($version == 4) {
                $response = array('signature' => signV4RestRequest($headersStr));
            }
            else {
                $response = array('signature' => sign($headersStr));
            }
            echo json_encode($response);
        }
        else {
            echo json_encode(array("invalid" => true));
        }
    }

    function isValidRestRequest($headersStr, $version) {
        if ($version == 2) {
            global $expectedBucketName;
            $pattern = "/\/$expectedBucketName\/.+$/";
        }
        else {
            global $expectedHostName;
            $pattern = "/host:$expectedHostName/";
        }
        preg_match($pattern, $headersStr, $matches);
        return count($matches) > 0;
    }

    function signPolicy($policyStr) {
        $policyObj = json_decode($policyStr, true);
        if (isPolicyValid($policyObj)) {
            $encodedPolicy = base64_encode($policyStr);
            if (isset($_REQUEST["v4"])) {
                $response = array('policy' => $encodedPolicy, 'signature' => signV4Policy($encodedPolicy, $policyObj));
            }
            else {
                $response = array('policy' => $encodedPolicy, 'signature' => sign($encodedPolicy));
            }
            echo json_encode($response);
        }
        else {
            echo json_encode(array("invalid" => true));
        }
    }

    function isPolicyValid($policy) {
        global $expectedMaxSize, $expectedBucketName;
        $conditions = $policy["conditions"];
        $bucket = null;
        $parsedMaxSize = null;
        for ($i = 0; $i < count($conditions); ++$i) {
            $condition = $conditions[$i];

            if (isset($condition["bucket"])) {
                $bucket = $condition["bucket"];
            }
            else if (isset($condition[0]) && $condition[0] == "content-length-range") {
                $parsedMaxSize = $condition[2];
            }
        }
        return $bucket == $expectedBucketName && $parsedMaxSize == (string)$expectedMaxSize;
    }

    function sign($stringToSign) {
        global $clientPrivateKey;
        return base64_encode(hash_hmac(
                'sha1',
                $stringToSign,
                $clientPrivateKey,
                true
            ));
    }

    function signV4Policy($stringToSign, $policyObj) {
        global $clientPrivateKey;
        foreach ($policyObj["conditions"] as $condition) {
            if (isset($condition["x-amz-credential"])) {
                $credentialCondition = $condition["x-amz-credential"];
            }
        }
        $pattern = "/.+\/(.+)\\/(.+)\/s3\/aws4_request/";
        preg_match($pattern, $credentialCondition, $matches);
        $dateKey = hash_hmac('sha256', $matches[1], 'AWS4' . $clientPrivateKey, true);
        $dateRegionKey = hash_hmac('sha256', $matches[2], $dateKey, true);
        $dateRegionServiceKey = hash_hmac('sha256', 's3', $dateRegionKey, true);
        $signingKey = hash_hmac('sha256', 'aws4_request', $dateRegionServiceKey, true);
        return hash_hmac('sha256', $stringToSign, $signingKey);
    }

    function signV4RestRequest($rawStringToSign) {
        global $clientPrivateKey;
        $pattern = "/.+\\n.+\\n(\\d+)\/(.+)\/s3\/aws4_request\\n(.+)/s";
        preg_match($pattern, $rawStringToSign, $matches);
        $hashedCanonicalRequest = hash('sha256', $matches[3]);
        $stringToSign = preg_replace("/^(.+)\/s3\/aws4_request\\n.+$/s", '$1/s3/aws4_request'."\n".$hashedCanonicalRequest, $rawStringToSign);
        $dateKey = hash_hmac('sha256', $matches[1], 'AWS4' . $clientPrivateKey, true);
        $dateRegionKey = hash_hmac('sha256', $matches[2], $dateKey, true);
        $dateRegionServiceKey = hash_hmac('sha256', 's3', $dateRegionKey, true);
        $signingKey = hash_hmac('sha256', 'aws4_request', $dateRegionServiceKey, true);
        return hash_hmac('sha256', $stringToSign, $signingKey);
    }


    // Provide a time-bombed public link to the file.
    function getTempLink($bucket, $key) {
        $client = getS3Client();
        $url = "{$bucket}/{$key}";
        $request = $client->get($url);
        return $client->createPresignedUrl($request, '+24 hours');
    }

    function getObjectSize($bucket, $key) {
        try {    
            $objInfo = getS3Client()->headObject(array(
                    'Bucket' => $bucket,
                    'Key' => $key
                ));
        } catch (Exception $e) {
          echo json_encode(array("error" => "$e"));
        }
        return $objInfo['ContentLength'];
    }
    function isFileViewableImage($filename) {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $viewableExtensions = array("jpeg", "jpg", "gif", "png");
        return in_array($ext, $viewableExtensions);
    }
?>
