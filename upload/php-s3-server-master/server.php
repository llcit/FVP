<?php
/* TO DO 12/18

1. Google Transcribe or Russian
2. DB stuff and get $pid
3. Write progress vals to cookie
4. Generate and upload thumb in ffmpeg
*/
    // blow open memory limit
    ini_set('memory_limit', '-1');
    require './vendor/autoload.php';
    $language = 'English';
    use Aws\S3\S3Client;
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
    $language = 'English';

    if ($method == 'OPTIONS') {
        handlePreflight();
    }
    else if ($method == "DELETE") {
        handleCorsRequest(); // only needed in a CORS environment
        deleteObject();
    }

    else if ($method == 'POST') {
        handleCorsRequest();
        if (isset($_REQUEST["success"])) {
            $tmpLink = verifyFileInS3();
            include_once("../../inc/db_pdo.php");
            $pid = registerVideo('4','2');
            $transcribeResult = generateTranscript($tmpLink,$_REQUEST['key']);
            $confirmation = confirmUpload($pid,$transcribeResult['duration'],$transcribeResult['success'],$tmpLink);
        }
        else {
            signRequest();
        }
    }
    function registerVideo($uid,$eid) {
        global $pdo;
        $sql ="SELECT id FROM presentations WHERE (user_id=? AND event_id=?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$uid,$eid]); 
        if($stmt->rowCount() > 0) {
            // presentation exists-- overwrite
            $result = $stmt->fetch(PDO::FETCH_OBJ);
            $pid = $result->id;
        } else {  
            // new presentation
            $sql = "INSERT INTO presentations (id,user_id,event_id) VALUES (?,?)";
            $stmt= $pdo->prepare($sql)->execute([$pid,$uid,$eid]);
            if($stmt->rowCount() == 0) {
                $pid = $pdo->lastInsertId();
            }
        } 
        return $pid;
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
    function confirmUpload($pid,$duration,$transcript_success,$link) {
        global $pdo;
        $response = array("tempLink" => $link);
        $sql = "UPDATE presentations (duration,transcript_success) VALUES (?,?)";
        $stmt= $pdo->prepare($sql)->execute([$duration,$transcript_success]);
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
        $url = $SETTINGS['WATSON_BASE_URL']."/".$models[$language].
               "/recognize?timestamps=true";
        echo("\n\nWatson URL: $url\n\n");
        $username = $SETTINGS['WATSON_USER'];
        $password = $SETTINGS['WATSON_PWD'];
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
        $fileContent = "WEBVTT\r\nKind: captions\r\nLanguage: en\r\n\r\n";
        $textType = 'captions';
        $raw_transcript = json_decode($data);
        foreach($raw_transcript->results as $result) {
            $start = time_format($result->alternatives[0]->timestamps[0][1]);
            $end = time_format($result->alternatives[0]->timestamps[count($result->alternatives[0]->timestamps)-1][2]);
            if ($textType == 'captions') {
                $fileContent .=  $start . " --> " . $end ."\r\n";
                $fileContent .= $result->alternatives[0]->transcript  ."\r\n\r\n";
                echo "--->" . $result->alternatives[0]->transcript ."\n";
            }
            else if ($textType == 'paragraph') {
                $fileContent .= $result->alternatives[0]->transcript ." ";
            }
            
        }
        $client = getS3Client();
        $result = $client->putObject(array(
            'Bucket' => $expectedBucketName,
            'Key'    => "transcripts/$pid.vtt",
            'Body'   => "$fileContent"
        ));
        $code = $result['@metadata']['statusCode'];
        $success = ($code === 200) ? true : false ;
        return $success;
    } 

    function transcribe_Google($audioFile,$language) {

        $languages = [
            'Russian' => 'ru'
        ];
      

        return true;
    }

    function time_format($rawTime) {
        if ($rawTime) {
            list($seconds, $ms) = preg_split("/\./",$rawTime);
            // always 0 microseconds
            $microseconds = '000';
            return gmdate("H:i:s", $seconds) . '' . $microseconds;
        }
    }

    function generateTranscript($tmpLink,$key) {
        global $SETTINGS,$language;
        $audio_extension = $SETTINGS['tmp_audio_extension'];
        preg_match("/(.*)\.(mov|mp4|m4a)/",$key,$matches);
        $file_name = $matches[1];
        $video_extension = $matches[2];
        echo ("\n\nRIP file_name: $file_name\n\n");
        echo ("\n\nRIP video_extension: $video_extension\n\n");
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
        if ($audio_extension == 'mp3') {
        	$output_format = new FFMpeg\Format\Audio\Mp3(); 
        	$output_format->setAudioCodec("libmp3lame");
        }
        if ($audio_extension == 'flac') {
        	$output_format = new FFMpeg\Format\Audio\Flac();  
        	$output_format->setAudioChannels(1);
        	$output_format->setAudioKiloBitrate(256);
        }
        $output_format->on('progress', function ($video, $format, $percentage) use($key) {
            file_put_contents('./progress/'. $key . '.txt', $percentage);
        }); 
        $saveFile = addslashes($output_dir . $file_name . "." . $audio_extension);
        $video->save($output_format, $saveFile); 
        $audioFile = $file_name . "." . $audio_extension;
        if ($language != 'Russian') {
            $response = transcribe_Watson($audioFile,$language);
        }
        else {
            $response = transcribe_Google($audioFile,$language);
        }
        $transcribeSuccess = writeVTTFile($response['file'],$response['response'],$language);

        $ffprobe = FFMpeg\FFProbe::create();
        $duration = $ffprobe
                            ->streams($saveFile) // extracts streams informations
                            ->videos()                      // filters video streams
                            ->first()                       // returns the first video stream
                            ->get('duration');              // returns the duration property
        return ['duration' => $duration, 'transcript_raw' => $transcribeSuccess];
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
