<?php

namespace Tests\Unit\Connection;

use Kit\Websocket\Connection\Connection;
use Kit\Websocket\Events\OnDataReceivedEvent;
use Kit\Websocket\Events\OnDisconnectEvent;
use Kit\Websocket\Events\OnNewConnectionOpenEvent;
use Kit\Websocket\Events\OnUpgradeEvent;
use Kit\Websocket\Frame\Enums\CloseFrameEnum;
use Kit\Websocket\Message\MessageWriter;
use Mockery\LegacyMockInterface;
use Mockery\MockInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use function React\Promise\resolve;
use function Tests\Helpers\getHandshake;



function getMockEventDispatcher(): LegacyMockInterface|MockInterface|EventDispatcherInterface
{
    return mock(EventDispatcherInterface::class);
}

function createSut(EventDispatcherInterface|MockInterface $eventDispatcher = null): Connection
{
    $eventDispatcher ??= getMockEventDispatcher();

    return new Connection(
        $eventDispatcher,
        spy(MessageWriter::class),
        '127.0.0.1'
    );
}

test('Should receive handshake correctly and dispatch upgrade event', function (): void {
    $dispatcher = mock(EventDispatcherInterface::class);
    $dispatcher->shouldReceive('dispatch')->with(OnUpgradeEvent::class)->andReturn(resolve('any_handshake_response'));
    $dispatcher->shouldReceive('dispatch')->with(OnNewConnectionOpenEvent::class)->andReturn(resolve(null));
    $sut = createSut($dispatcher);
    $sut->getEventDispatcher();
    $sut->onMessage(getHandshake());
    expect($sut->isHandshakeDone())->toBeTrue();
});

test('Should call OnDataReceivedEvent successfully after handshake', function (): void {
    $dispatcher = mock(EventDispatcherInterface::class);
    $dispatcher->shouldReceive('dispatch')->with(OnUpgradeEvent::class)->andReturn(resolve('any_handshake_response'));
    $dispatcher->shouldReceive('dispatch')->with(OnNewConnectionOpenEvent::class)->andReturn(resolve(null));
    $dispatcher->expects('dispatch')->with(OnDataReceivedEvent::class)->andReturn(resolve(null));

    $connection = createSut($dispatcher);
    $connection->onMessage(getHandshake());
    $connection->onMessage('');

    expect($connection->isHandshakeDone())->toBeTrue();

});

test('Should call on disconnect correctly after successful handshake', function (): void {
    $dispatcher = mock(EventDispatcherInterface::class);
    $dispatcher->shouldReceive('dispatch')->with(OnUpgradeEvent::class)->andReturn(resolve('any_handshake_response'));
    $dispatcher->shouldReceive('dispatch')->with(OnNewConnectionOpenEvent::class)->andReturn(resolve(null));
    $dispatcher->shouldReceive('dispatch')->with(OnDisconnectEvent::class)->andReturn(resolve(null));

    $connection = createSut($dispatcher);
    $connection->onMessage(getHandshake());
    $connection->onEnd();

    expect($connection->isHandshakeDone())->toBeTrue();

});

test('Should call early desconnection when absent handshake', function (): void {
    $dispatcher = mock(EventDispatcherInterface::class);
    $dispatcher->shouldNotReceive('dispatch')->withAnyArgs();

    $connection = createSut($dispatcher);
    $connection->onEnd();

    expect($connection->isHandshakeDone())->toBeFalse();
});

test('Should call close correctly', function (): void {
    $connection = createSut();
    /** @var MockInterface */
    $writer = $connection->getSocketWriter();
    $writer->expects('close')->with(CloseFrameEnum::CLOSE_NORMAL, null);
    expect($connection->close(CloseFrameEnum::CLOSE_NORMAL))->toBeNull();
});
