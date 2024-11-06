<?php

namespace Tests\Unit\Connection;

use Kit\Websocket\Connection\Connection;
use Kit\Websocket\Connection\Connector;
use Kit\Websocket\Events\OnDisconnectEvent;
use Kit\Websocket\Events\OnNewConnectionOpenEvent;
use Kit\Websocket\Events\OnUpgradeEvent;
use Kit\Websocket\Events\Protocols\EventDispatcher;
use Kit\Websocket\Frame\Enums\FrameTypeEnum;
use Kit\Websocket\Http\Request;
use Kit\Websocket\Message\Message;
use Kit\Websocket\Message\MessageWriter;
use Kit\Websocket\Message\Protocols\MessageHandlerInterface;

use Mockery\LegacyMockInterface;
use Mockery\MockInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use function Kit\Websocket\functions\hexArrayToString;
use function React\Promise\resolve;
use function Tests\Helpers\createMessageProcessor;
use function Tests\Helpers\createMessageWriter;
use function Tests\Helpers\createTimeoutHandler;
use function Tests\Helpers\getHandshake;
use function Tests\Helpers\mockMessage;
use function Tests\Helpers\readTempZip;


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

test('Should receive handshake correctly and dispatch upgrade event', function () {
    $dispatcher = mock(EventDispatcherInterface::class);
    $dispatcher->shouldReceive('dispatch')->with(OnUpgradeEvent::class)->andReturn(resolve('any_handshake_response'));
    $dispatcher->shouldReceive('dispatch')->with(OnNewConnectionOpenEvent::class)->andReturn(resolve(null));
    $sut = createSut($dispatcher);
    $sut->getEventDispatcher();
    $sut->onMessage(getHandshake());
    expect($sut->isHandshakeDone())->toBeTrue();
});

// test('Should support text message', function (): void {
//     $helloFrame = hexArrayToString(['81', '05', '48', '65', '6c', '6c', '6f']);
//     // Mock message properties
//     $message = mockMessage(FrameTypeEnum::Text, 'Hello');
//     $messageProcessor = createMessageProcessor();
//     $messageProcessor->shouldReceive('write')->withAnyArgs();
//     $messageProcessor->shouldReceive('process')->with($helloFrame, null)->andReturn(generateMessage($message));
//     $handlerSpy = spy(MessageHandlerInterface::class);
//     // Expectations
//     $handlerSpy->shouldReceive('supportsFrame')->withAnyArgs()->once()->andReturn(true);

//     // Test initialization
//     $connection = new Connection(
//         messageHandlerInterface: $handlerSpy,
//         messageProcessor: $messageProcessor,
//         timeoutHandler: createTimeoutHandler(),
//         ip: TEST_LOCAL_IP
//     );
//     $connection->onMessage(getHandshake());
//     $connection->onMessage($helloFrame);

//     $handlerSpy->shouldHaveReceived('onOpen')->once();
//     $handlerSpy->shouldHaveReceived('handle')->with($message->getContent(), $connection)->once();
// });

// test('Should support binary message', function (): void {
//     // Data and mock setup
//     $handshake = getHandshake();
//     $binary = readTempZip();
//     $binaryFrame = hexArrayToString(['82', '7A']) . $binary;

//     // Mock message properties
//     $message = mockMessage(FrameTypeEnum::Binary, $binary);

//     $messageProcessor = createMessageProcessor();

//     $messageProcessor->shouldReceive('write')->withAnyArgs();
//     $messageProcessor->shouldReceive('process')->with($binaryFrame, null)->andReturn(generateMessage($message));
//     $handlerSpy = spy(MessageHandlerInterface::class);
//     $handlerSpy->shouldReceive('supportsFrame')->withAnyArgs()->once()->andReturn(true);


//     // Test initialization
//     $connection = new Connection(
//         messageHandlerInterface: $handlerSpy,
//         messageProcessor: $messageProcessor,
//         timeoutHandler: createTimeoutHandler(),
//         ip: TEST_LOCAL_IP
//     );
//     $connection->onMessage($handshake);
//     $connection->onMessage($binaryFrame);

//     $handlerSpy->shouldHaveReceived('onOpen')->with($connection)->once();
//     $handlerSpy->shouldHaveReceived('handle')->with($message->getContent(), $connection)->once();
// });

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
