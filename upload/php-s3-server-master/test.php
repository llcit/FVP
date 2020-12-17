<?php

require './vendor/autoload.php';

$key = "6ee47445-8cd2-4aea-be03-b0da492e21a3.mov";
 preg_match("/\.(mov|mp3|m4a)/",$key,$matches);
 $video_extension = $matches[1];
 echo ("\n\nRIP video_extension: $video_extension\n\n");
 die();
 /*
$client = new \GuzzleHttp\Client();

$req = $client->createRequest('GET', 'http://www.google.com', array(
    'future' => true,
));
echo "Sending request\n";
$response = $client->send($req);

try {
    $response->then(function ($data) {
        echo "Response 1 is received\n";

        $client2 = new \GuzzleHttp\Client();

        $req2 = $client2->createRequest('GET', 'http://www.yahoo.com', array(
            'future' => true,
        ));
        echo "Sending request 2 \n";
        $response2 = $client2->send($req);

        try {
            $response2->then(function ($data) {
                echo "Response 2 is received\n";
                throw new Exception('Test');
            })->then(function () {
                // success handler
            }, function (Exception $exception) {
                echo "Error handler invoked\n";
                throw $exception;
            });
        } catch (Exception $e) {
            echo "Exception catched\n";
        }
        echo "Finish 2\n";


    })->then(function () {
        // success handler
    }, function (Exception $exception) {
        echo "Error handler invoked\n";
        throw $exception;
    });
} catch (Exception $e) {
    echo "Exception catched\n";
}
echo "Finish 1 \n";






$afreq = $client->createRequest('GET', $SETTINGS['base_url'] . "/upload/ripAudio.php?tl=654", array(
    'future' => true,
));
echo "Sending request for audio File\n";
$audioFileResponse = $client->send($afreq);

try {
    $audioFileResponse->then(function ($data) {
        echo "Audio file response is received\n";
        //throw new Exception('Test');
    })->then(function () {
        $treq = $client->createRequest('GET', $SETTINGS['base_url'] . "/upload/transcript.php?af=654", array(
            'future' => true,
        ));
        echo "Sending request for transcript\n";
        $transcriptResponse = $client->send($treq);
    }, function (Exception $exception) {
        echo "Error handler invoked\n";
        throw $exception;
    });
} catch (Exception $e) {
    echo "Exception catched\n";
}
echo "Finish\n";








use Spatie\Async\Pool;
$pool = Pool::create();

// Generate 10k processes generating random numbers
for($i = 0; $i < 10000; $i++) {
    $pool->add(function() use ($i) {
        echo($i);
        return rand(0, 100);
    })->then(function($output) use ($pool) {
        // If one of them randomly picks 100, end the pool early.
        if ($output === 100) {
            echo('pots!');
            $pool->stop();
        }
    });
}

$pool->wait();

require './vendor/autoload.php';

use Spatie\Async\Pool;
$mypool = Pool::create();
$mypool[] = async() {
        //countup();
        echo('1');
})->then() {
        //countDown();
        echo('2');
});

function countup() {
        for ($i=0;$i<=1000000000000) {
                $x=$i;
        }
        echo('countup done');
}
function countdown() {
        for ($i=1000000000000;$i>0;$i--) {
                $x=$i;
        }
        echo('countup done');
}
*/
