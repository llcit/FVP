<?php

require './vendor/autoload.php';

ripAudio("https://s3.amazonaws.com/flagship-video-project/f460f441-56ab-45d6-b6ad-e42c7a18c7b7.mov?AWSAccessKeyId=AKIA5GGQGDXJ7N57DNHH&Expires=1608301812&Signature=kf8aAQuQulWK%2BTwXKNJJCBzgB%2Bw%3D","f460f441-56ab-45d6-b6ad-e42c7a18c7b7.mov");
function ripAudio($tmpLink,$key) {


        //start here

        $audio_extension = 'flac';
        preg_match("/\.(mov|mp3|m4a)/",$key,$matches);
        $video_extension = $matches[1];
        echo ("\n\nRIP video_extension: $video_extension\n\n");
        $output_dir = './tmpAudio/';
        $in_file = '';
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
            $output_format->setAudioChannels(2);
            $output_format->setAudioKiloBitrate(256);
        }
        $output_format->on('progress', function ($video, $format, $percentage) use($key) {
            file_put_contents('./progress/'. $key . '.txt', $percentage);
        }); 
        $saveFile = addslashes($output_dir . $key . "." . $audio_extension);
        $video->save($output_format, $saveFile)
        ->then( function() {return $key . "." . $audio_extension;}); 
    } 
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
