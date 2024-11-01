<?php

namespace Tests\Unit\Connection;

use Kit\Websocket\Connection\TimeoutHandler;
use React\EventLoop\LoopInterface;
use function React\Promise\reject;
use function React\Promise\resolve;

test('Should call timeout correctly when promise timeout is resolved', function (): void {
    $mockLoopInterface = mock(LoopInterface::class);
    $mockLoopInterface->shouldReceive('addTimer')->andReturnUsing(function ($count, $action): void {
        usleep($count);
        expect($count)->toBe(1);
        $action();
    });
    $action = new class () {
        public function __construct(public int $calls = 0)
        {
        }
    };
    $timeoutHandler = new TimeoutHandler($mockLoopInterface, 1);
    $timeoutHandler->setTimeoutAction(\Closure::bind(fn() => $this->calls++, $action));
    $timeoutHandler->handleConnectionTimeout(resolve(true));
    expect($action->calls)->toBe(1);
});

test('Should NOT call timeout when promise timeout is rejected', function (): void {
    $mockLoopInterface = mock(LoopInterface::class);
    $mockLoopInterface->shouldNotReceive('addTimer');
    $action = new class () {
        public function __construct(public int $calls = 0)
        {
        }
    };
    $timeoutHandler = new TimeoutHandler($mockLoopInterface, 1);
    $timeoutHandler->setTimeoutAction(\Closure::bind(fn() => $this->calls++, $action));
    $timeoutHandler->handleConnectionTimeout(reject(new \Error()));
    expect($action->calls)->toBe(0);
});

