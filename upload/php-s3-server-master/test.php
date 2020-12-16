<?php

require './vendor/autoload.php';

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
