<?php
    require './vendor/autoload.php';
    use Aws\S3\S3Client;

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
    else if ($method == "DELETE") {
        handleCorsRequest(); // only needed in a CORS environment
        deleteObject();
    }

    else if	($method == 'POST') {
        handleCorsRequest();
        if (isset($_REQUEST["success"])) {
            verifyFileInS3(shouldIncludeThumbnail());
            ripAudio($_REQUEST["bucket"],$_REQUEST["key"])
        }
        else {
            signRequest();
        }
    }
    function ripAudio($bucket,$key) {
        $audio_extension = 'flac';
        $video_extension = 'mp4';

        $in_file = '';
        $bucket = $argv[1];
        $key = $argv[2];


        if ($stream = fopen("s3://$bucket/$key", 'r')) {
            // While the stream is still open
            while (!feof($stream)) {
                // Read 1,024 bytes from the stream
                $in_file .= fread($stream, 1024);
            }
            // Be sure to close the stream resource when you're done with it
            fclose($stream);
        }

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

    // Only needed in cross-origin setups
    function handleCorsRequest() {
        // If you are relying on CORS, you will need to adjust the allowed domain here.
        header('Access-Control-Allow-Origin: http://fineuploader.com');
    }

    // Only needed in cross-origin setups
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

    // Only needed if the delete file feature is enabled
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

    // This is not needed if you don't require a callback on upload success.
    function verifyFileInS3($includeThumbnail) {
        global $expectedMaxSize;

        $bucket = $_REQUEST["bucket"];
        $key = $_REQUEST["key"];
    	echo("MAX SIZE: " . $expectedMaxSize ."\n\n");
            echo("ACTUAL SIZE: " . getObjectSize($bucket, $key) ."\n\n"); 
       if (isset($expectedMaxSize) && getObjectSize($bucket, $key) > $expectedMaxSize) {
            // You can safely uncomment this next line if you are not depending on CORS
            header("HTTP/1.0 500 Internal Server Error");
            deleteObject();
            echo json_encode(array("error" => "File is too big!", "preventRetry" => true));
        }
        else {
            $link = getTempLink($bucket, $key);
    	echo("GOT  TMP LINK -> $link\n\n");
            $response = array("tempLink" => $link);

            if ($includeThumbnail) {
                $response["thumbnailUrl"] = $link;
            }

            echo json_encode($response);
        }
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
            echo("BUCKET: " .$bucket . "\n\n");
            echo("KEY: " .$key . "\n\n");
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
    function shouldIncludeThumbnail() {
        $filename = $_REQUEST["name"];
        $isPreviewCapable = $_REQUEST["isBrowserPreviewCapable"] == "true";
        $isFileViewableImage = isFileViewableImage($filename);

        return !$isPreviewCapable && $isFileViewableImage;
    }
?>
