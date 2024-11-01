<?php

use React\EventLoop\Loop;
use React\Promise\Promise;
use function React\Async\{await, async};
use function Kit\Websocket\functions\println;

require_once 'vendor/autoload.php';

Loop::addTimer(0.5, async(function () {
    println('a');
    $value = async(function(){
        $fiber = new Fiber(function($firstValue){
            sleep(1);
            println('Test');
            Fiber::suspend();
            println($firstValue);
        }) ;
        println('d');
        $fiber->start('Blablabla');
        println("Started fiber");
        $fiber->resume();
    });
    $value();
    println('c');
}));

Loop::addTimer(1.0, function () {
    println('b');
});

Loop::addTimer(3.0, function () {
    println('finished');
});

Loop::run();
