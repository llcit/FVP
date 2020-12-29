<?php
    require '/var/www/html/FVP/upload/php-s3-server-master//vendor/autoload.php';
    use Aws\S3\S3Client;
    function translateVTTFile($pid) {
        $SETTINGS = parse_ini_file(__DIR__."/../../inc/settings.ini");
        $clientPrivateKey = $SETTINGS['AWS_CLIENT_SECRET_KEY'];
        $serverPublicKey = $SETTINGS['AWS_SERVER_PUBLIC_KEY'];
        $serverPrivateKey = $SETTINGS['AWS_SERVER_PRIVATE_KEY'];
        $expectedBucketName = $SETTINGS['S3_BUCKET_NAME'];

        $type = 'transcript';
        $ext = 'vtt';
        $key =  $type . "s/".$pid."." . $ext;
      
        $client = getS3Client();
        $client->registerStreamWrapper();
        if ($stream = fopen("s3://$expectedBucketName/$key", 'r')) {
            // While the stream is still open
            while (!feof($stream)) {
                // Read 1024 bytes from the stream
                $transcriptContents = fread($stream, 1024);
            }
            // Be sure to close the stream resource when you're done with it
            fclose($stream);
        }
        $parsedCaptions = parseCaptions($transcriptContents);
        $translatedContents = "WEBVTT\nKind: captions\nLanguage: en\n\n";
        for ($i=0;$i<count($parsedCaptions);$i++) {
            $translatedContents .= $parsedCaptions[$i]['timeCodes'] . "\n";
            $translatedContents .= $parsedCaptions[$i]['translation']. "\n\n";
        }
        $command = $client->getCommand('PutObject', array(
                    'Bucket' => $expectedBucketName,
                    'Key'    => "translations/$pid.vtt",
                    'Body'   => "$translatedContents"
            ));
        $result = $command->getResult();
        $response = $command->getResponse();
        $code = $response->getStatusCode();
        $success = ($code === 200) ? true : false ;
        return $success;
    }
    function parseCaptions($transcriptContents){
        $lines = preg_split("/\\n/", $transcriptContents);
        // do not start capturing text until we are past the header
        $captureText = false;
        $count = -1;
        // split out the target language, the time codes and the original texts
        $parsedCaptions = [];
        // 1st pass combine lines with carriage returns into a single entry
        $fragments = [];
        foreach ($lines as $line) {
            if (substr($line, 0, 8) == 'Language') {
                // grab the 2-char language code from the transcript
                $targetLanguage = substr($line, 10,12);
                $captureText = true;
            }
            else if (substr($line, 0, 3) == '00:'){
                $count++;
                $parsedCaptions[$count]['timeCodes'] = $line;
            }
            else if ($line != "" && $captureText) {
                $parsedCaptions[$count]['text'] .= $line;
                $captionSplitsSentence = (preg_match("/\.|\?|\!|\。/",substr($line, -1))) ? false : true;
                $captionContainsSentenceEnd = (preg_match("/\.|\?|\!|\。/",$line)) ? true : false;
                if (count($fragments)> 0 && $captionContainsSentenceEnd) {
                    $backFill=true;
                }
                $sentences = preg_split("/\.|\?|\!|\。/",$line,$matches);
                if ($captionSplitsSentence)  {
                    array_push($fragments, $sentences[count($sentences)-1]);
                    unset($sentences[count($sentences)-1]);
                }
                // translate each of the complete sentences found in the caption
                for ($i=0;$i<count($sentences);$i++) {
                    if ($backFill && $captionContainsSentenceEnd) {
                        // add the final fragment from the beginning of the line
                        array_push($fragments, $sentences[0]);
                        // compile the whole sentence for translation
                        for($i=0;$i<count($fragments);$i++) {
                            $fullSentence .= $fragments[$i] . ' ';
                        }
                        if ($fullSentence != '') {
                            echo("Time: " . $parsedCaptions[$count]['timeCodes'] . "<br>");
                            echo("fullSentence: " . $fullSentence . "<br>");
                            $translation = translate($fullSentence,$targetLanguage);
                            echo("translation: " . $translation . "<br>");
                            backFillSentenceFragments($parsedCaptions,$count,$fragments,$fullSentence,$translation);
                            $fullSentence = "";
                        }
                        $fragments = [];
                        $backFill = false;
                    } 
                    else {
                        if ($sentences[$i] != '') {
                            echo("Time: " . $parsedCaptions[$count]['timeCodes'] . "<br>");
                            echo("SENTENCE[$i]: " . $sentences[$i] . "<br>");
                            $translation = translate($sentences[$i],$targetLanguage);
                            echo("translation: " . $translation . "<br>");
                            $parsedCaptions[$count]['translation'] .= $translation;
                        }
                    }
                }
            }
        }
        return $parsedCaptions;
    }
    function backFillSentenceFragments(&$parsedCaptions,$currentLine,$fragments,$fullSentence, $translation) {
        $percentSplits = [];
        $targetSentenceLength = mb_strlen($fullSentence, 'UTF-8');
        $rowsBack = count($fragments)-1;
        for($i=0;$i<count($fragments);$i++) {
            $fragmentLength = mb_strlen($fragments[$i] . ' ', 'UTF-8');
            $percentSplits[$i] =  ['lineNumber' => ($currentLine - $rowsBack),'percentage' => ($fragmentLength/$targetSentenceLength)];
            $rowsBack--;
        }
        $translatedSentenceLength = mb_strlen($translation, 'UTF-8');
        $words = preg_split("/\ /",$translation);
        $count = 0;
        $newFragment = '';
        for($i=0;$i<count($words);$i++) {
            $newFragment .= $words[$i] . ' ';
            $newFragmentLength = mb_strlen($newFragment, 'UTF-8');
            if (($newFragmentLength/$translatedSentenceLength) > $percentSplits[$count]['percentage']) {
                $parsedCaptions[$percentSplits[$count]['lineNumber']]['translation'] .= $newFragment . ' ';
                $newFragment = '';
                $count++;
            }
        }
        $parsedCaptions[$currentLine]['translation'] = $newFragment . ' ';
    }

    function translate($text,$targetLanguage) {
        echo("TRANSLATING: " . $text . "<br>");
        $data = ['text' => [$text],'model_id'=>$targetLanguage.'-en'];
        $url = $SETTINGS['WATSON_TRANSLATE_URL'];
        $apiKey = $SETTINGS['WATSON_TRANSLATE_KEY'];;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        $headers = ["Content-Type: application/json"];
        curl_setopt($ch, CURLOPT_POST,1);
        curl_setopt($ch, CURLOPT_USERPWD, "apikey:$apiKey");
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS,json_encode($data));
        $response = curl_exec($ch);
        $status_code = curl_getinfo($ch,CURLINFO_HTTP_CODE);
        if (!in_array($status_code, [200,201])) {
            throw new Exception("GOT $status_code FROM $url:\n$response");
        }
        if ($response) {
            $response =  json_decode($response);
            return $response->translations[0]->translation;
        } else {
            return true;
        }
    }

    function time_format($rawTime) {
        list($seconds, $microseconds) = preg_split("/\./",$rawTime);
        if (!$microseconds) {
            $microseconds = '00';
        }
        else if (strlen($microseconds) == 1) {
            $microseconds = '0' . $microseconds;
        }
        return gmdate("H:i:s", $seconds) . ',' . $microseconds;
    }
    function getS3Client() {
        global $clientPrivateKey, $serverPrivateKey;
        return S3Client::factory(array(
            'key' => $serverPrivateKey,
            'secret' => $clientPrivateKey
        ));
    }