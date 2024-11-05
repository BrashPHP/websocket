<?php

namespace Tests\Unit\Connection;

use Kit\Websocket\Connection\Connection;
use Kit\Websocket\Connection\TimeoutHandler;
use Kit\Websocket\Events\Protocols\EventDispatcher;
use Mockery\LegacyMockInterface;
use Mockery\MockInterface;
use React\EventLoop\TimerInterface;
use React\Promise\Deferred;

use function React\Promise\resolve;
use function Tests\Helpers\createMockLoopInterface;

function createConnectionObject(): LegacyMockInterface|MockInterface|Connection{
    return spy(Connection::class);
}

test('Should call timeout correctly when promise timeout is resolved', function (): void {
    $mockLoopInterface = createMockLoopInterface();
    $mockLoopInterface->shouldReceive('addTimer')->andReturnUsing(function ($count, $action): void {
        usleep($count);
        expect($count)->toBe(1);
        $action();
    });

    $timeoutHandler = new TimeoutHandler($mockLoopInterface, 1);
    $spyConnection = createConnectionObject();
    
    $timeoutHandler->handleConnectionTimeout(resolve($spyConnection));
    $spyConnection->shouldHaveReceived('timeout')->once();
});

test('Should NOT call timeout when promise timeout is pending', function (): void {
    $mockLoopInterface = createMockLoopInterface();
    $mockLoopInterface->shouldNotReceive('addTimer');
    $timeoutHandler = new TimeoutHandler($mockLoopInterface, 1);
    $deferred = new Deferred();
    $timeoutHandler->handleConnectionTimeout($deferred->promise());
});

test('Should stop timer when handle connection is called within time window', function (): void {
    $mockLoopInterface = createMockLoopInterface();
    $mockInterval = mock(TimerInterface::class);
    $mockLoopInterface->shouldReceive('addTimer')->withAnyArgs()->andReturn($mockInterval)->once();
    $mockLoopInterface->shouldReceive('cancelTimer')->with($mockInterval)->once();
    $timeoutHandler = new TimeoutHandler($mockLoopInterface, 1);
    $timeoutHandler->handleConnectionTimeout(resolve(createConnectionObject()));
    $deferred = new Deferred();
    $timeoutHandler->handleConnectionTimeout($deferred->promise());
});

