<?php

require './vendor/autoload.php';
use GuzzleHttp\Promise\Promise;

$linkPromise = new Promise(); 
$linkPromise->resolve(true);
$audioPromise = new Promise();
$audioPromise->resolve(true);
$transcriptPromise = new Promise();
$transcriptPromise->resolve(true);

$linkPromise
    ->then(function ($value) use ($audioPromise) {
        echo "\nLink Promise in: $value\n";
	$tmpLink = getTmpLink();
	echo "\nLink pPromise out: $tmpLink\n";
        return $tmpLink;
    })
    ->then(function ($tmpLink) use ($transcriptPromise) {
        echo "\nAudio Promise in: $tmpLink\n";
        $audioFile = ripAudio($tmpLink);
        echo "\nAudio Promise out: $audioFile\n";
        return $audioFile;
    })
    ->then(function ($audioFile) {
        echo "\nTranscript Promise in: $audioFile\n";
        $transcript = transcribe($audioFile);
        echo "\nTranscript Promise out: $transcript\n";
        return $transcript;
    });

function getTmpLink() {
        sleep(1);
        return 'http://aws.com ';
}
function ripAudio($tmpLink) {
	echo("\n--RIPAUDIO got tmpLink: $tmpLink\n");
	sleep(15);
	return 'file.flac';
}

function transcribe($audioFile) {
	echo("\n--TRANSCRIBE got audioFile: $audioFile\n");
        sleep(3);
        return 'file.vtt';
}
