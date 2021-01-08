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
        $transcriptContents = '';
        if ($stream = fopen("s3://$expectedBucketName/$key", 'r')) {
            while (!feof($stream)) {
                $transcriptContents .= fread($stream, 1024);
            }
            fclose($stream);
        }
        $parsedCaptions = parseCaptions($transcriptContents);
        $translatedContents = "WEBVTT\nKind: captions\nLanguage: en\n\n";
        for ($i=1;$i<=count($parsedCaptions);$i++) {
            $translatedContents .= $parsedCaptions[$i]['timeCodes'] . "\n";
            $translatedContents .= $parsedCaptions[$i]['translated_text']. "\n\n";
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
        global $targetLanguage;
        $lines = preg_split("/\\n/", $transcriptContents);
        // do not start capturing text untile we are past the header
        $captureText = false;
        // split out the target language, the time codes and the original texts
        $parsedCaptions = [];
        // store time codes and raw text data in an array
        // raw text may include carriage returns that need to be stripped before processing.
        $lineData = [];
        $lineNumber = 0;
        $sentences = [];
        $sentenceNumber = 0;
        $sentences[$sentenceNumber]['line_proportions'] = [];
        foreach ($lines as $line) {
            if (substr($line, 0, 8) == 'Language') {
                // grab the 2-char language code from the transcript
                $targetLanguage = substr($line, 10,2);
                $captureText = true;
            }
            else if (preg_match("/\d{2}\:\d{2}\.\d{3}\ \-\-\>\ \d{2}\:\d{2}\.\d{3}/",$line)){
                $lineNumber++;
                $lineData[$lineNumber]['timeCodes'] = trim($line);
            }
            else if ($line != "" && $captureText) {
                // account for carriage returns and add white space when joining multi line entries
                $lineData[$lineNumber]['original_text'] .= ' ' .$line;
                // remove white space from beginning and end
                $lineData[$lineNumber]['original_text'] = trim($lineData[$lineNumber]['original_text']);
            }
        }
        for ($i=1;$i<=count($lineData);$i++) {
            // swap elipsis with unicode variant thereof
            $lineData[$i]['original_text'] = preg_replace('/\.{3}/', '…', $lineData[$i]['original_text']);
            $lineFragments = preg_split("/\.|\?|\!|\。|…/",$lineData[$i]['original_text']);
            $finalChar = substr($lineData[$i]['original_text'], -1);
            $sentenceIncomplete = (preg_match("/\.|\?|\!|\。|\.{3}/",$finalChar)) ? false : true;
            preg_match_all("/(\.|\?|\!|\。|…)/",$lineData[$i]['original_text'],$matches);
            $punctuationMarks = $matches[1]; 
            for ($j=0;$j<count($lineFragments);$j++) {
                // skip null entries from split on final punctuation
                if ($lineFragments[$j] != '') {
                    $sentences[$sentenceNumber]['sentence'] .= $lineFragments[$j] . ' ';
                    $sentences[$sentenceNumber]['punctuation_mark'] .= $punctuationMarks[$j] . ' ';
                    $fragmentData = [
                            'lineNumber'=> $i,
                            'length'=> mb_strlen($lineFragments[$j], 'UTF-8')
                    ];
                    array_push($sentences[$sentenceNumber]['line_proportions'],$fragmentData);
                    if ($i != count($lineData) && ($j < count($lineFragments)-1 || !$sentenceIncomplete)) {
                        $sentenceNumber++;
                        $sentences[$sentenceNumber]['line_proportions'] = [];
                    }
                }
            }
        }
        for($i=0;$i<count($sentences);$i++) {
            echo("Sentence in: " . $sentences[$i]['sentence'] . "<br>");
            $translation = translate(trim($sentences[$i]['sentence']),$targetLanguage);
            echo("Translation: " . $translation . "<br>");
            // if the entire sentence goes on one line, just add it to the line
            if (count($sentences[$i]['line_proportions'])<2) {
                $lineNum = $sentences[$i]['line_proportions'][0]['lineNumber'];
                $lineData[$lineNum]['translated_text'] .= ' ' . $translation;
            }
            //otherwise, we neet to split it across lines
            else {
                $originalSentenceLength = mb_strlen($sentences[$i]['sentence'], 'UTF-8');
                $translationLength = mb_strlen($translation, 'UTF-8');
                $transWords = preg_split("/\ /", $translation);
                // running count of words processed across lines
                $wordCount = 0;
                // build fragment of translated sentence to measure and apportion to lines
                $tmpString = '';
                // the proportion of the translated fragment to the entire translated sentence
                $proportion = 0;
                for ($j=0;$j<count($sentences[$i]['line_proportions']);$j++) {
                    $lineNum = $sentences[$i]['line_proportions'][$j]['lineNumber'];
                    $targetProportion = $sentences[$i]['line_proportions'][$j]['length']/$originalSentenceLength;
                    for ($k=$wordCount;$k<count($transWords);$k++) {
                        // build fragment and add space
                        $tmpString .= $transWords[$k] . ' ';
                        $tmpLength = mb_strlen($tmpString, 'UTF-8');
                        $proportion = $tmpLength/$translationLength;
                        if ($proportion >= $targetProportion || $k == count($transWords)-1) {
                            $distanceFromTarget = abs($proportion - $targetProportion);
                            $prevDistance = abs($prevProportion - $targetProportion);
                            if ($distanceFromTarget < $prevDistance || $j == count($sentences[$i]['line_proportions'])-1) {
                                $lineData[$lineNum]['translated_text'] .= $tmpString;
                                $wordCount = $k+1; 
                                $tmpString = '';
                                break;
                            }
                            else {
                                $lineData[$lineNum]['translated_text'] .= $prevString;
                                $wordCount = $k;
                                $tmpString = '';
                                break; 
                            }
                        } 
                        // store current as previous to measure closest cut point by word
                        $prevProportion = $proportion;
                        $prevString = $tmpString;
                    }
                }
            }
            // clean up sentence and add punctuation mark
            $lineData[$lineNum]['translated_text'] = preg_replace("/[ \t]+/"," ",$lineData[$lineNum]['translated_text']);
            $lineData[$lineNum]['translated_text'] = trim($lineData[$lineNum]['translated_text']);
            $lineData[$lineNum]['translated_text'] .= trim($sentences[$i]['punctuation_mark']) . ' ';
        }
        return $lineData;
    }
    function translate($text,$targetLanguage) {
        global $SETTINGS;
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
        if ($rawTime) {
            list($seconds, $ms) = preg_split("/\./",$rawTime);
            // always 0 microseconds
            $microseconds = '000';
            return gmdate("H:i:s", $seconds) . '.' . $microseconds;
        }
    }
    function getS3Client() {
        global $clientPrivateKey, $serverPrivateKey;
        return S3Client::factory(array(
            'key' => $serverPrivateKey,
            'secret' => $clientPrivateKey
        ));
    }