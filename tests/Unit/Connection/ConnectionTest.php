<?php

namespace Tests\Unit\Connection;

use Kit\Websocket\Connection\Connection;
use Kit\Websocket\Frame\Enums\FrameTypeEnum;
use Kit\Websocket\Message\Message;
use Kit\Websocket\Message\Protocols\MessageHandlerInterface;

use function Kit\Websocket\functions\hexArrayToString;
use function Tests\Helpers\createMessageProcessor;
use function Tests\Helpers\createTimeoutHandler;
use function Tests\Helpers\getHandshake;
use function Tests\Helpers\mockMessage;
use function Tests\Helpers\readTempZip;

function generateMessage(Message ...$messages)
{
    foreach ($messages as $message) {
        yield $message;
    }
}

define('TEST_LOCAL_IP', '127.0.0.1');

test('Should support text message', function () {
    $helloFrame = hexArrayToString(['81', '05', '48', '65', '6c', '6c', '6f']);
    // Mock message properties
    $message = mockMessage(FrameTypeEnum::Text, 'Hello');
    $messageProcessor = createMessageProcessor();
    $messageProcessor->shouldReceive('write')->withAnyArgs();
    $messageProcessor->shouldReceive('process')->with($helloFrame, null)->andReturn(generateMessage($message));
    $handlerSpy = spy(MessageHandlerInterface::class);
    // Expectations
    $handlerSpy->shouldReceive('supportsFrame')->withAnyArgs()->once()->andReturn(true);

    // Test initialization
    $connection = new Connection(
        messageHandlerInterface: $handlerSpy,
        messageProcessor: $messageProcessor,
        timeoutHandler: createTimeoutHandler(),
        ip: TEST_LOCAL_IP
    );
    $connection->onMessage(getHandshake());
    $connection->onMessage($helloFrame);

    $handlerSpy->shouldHaveReceived('onOpen')->once();
    $handlerSpy->shouldHaveReceived('handle')->with($message->getContent(), $connection)->once();
});

test('', function () {
    // Data and mock setup
    $handshake = getHandshake();
    $binary = readTempZip();
    $binaryFrame = hexArrayToString(['82', '7A']) . $binary;

    // Mock message properties
    $message = mockMessage(FrameTypeEnum::Binary, $binary);

    $messageProcessor = createMessageProcessor();

    $messageProcessor->shouldReceive('write')->withAnyArgs();
    $messageProcessor->shouldReceive('process')->with($binaryFrame, null)->andReturn(generateMessage($message));
    $handlerSpy = spy(MessageHandlerInterface::class);
    $handlerSpy->shouldReceive('supportsFrame')->withAnyArgs()->once()->andReturn(true);


    // Test initialization
    $connection = new Connection(
        messageHandlerInterface: $handlerSpy,
        messageProcessor: $messageProcessor,
        timeoutHandler: createTimeoutHandler(),
        ip: TEST_LOCAL_IP
    );
    $connection->onMessage($handshake);
    $connection->onMessage($binaryFrame);

    $handlerSpy->shouldHaveReceived('onOpen')->with($connection)->once();
    $handlerSpy->shouldHaveReceived('handle')->with($message->getContent(), $connection)->once();
});

test('Should call on disconnect correctly', function () {
    $messageProcessor = createMessageProcessor();
    $messageProcessor->shouldReceive('write')->withAnyArgs();
    $handlerSpy = spy(MessageHandlerInterface::class);
    // Test initialization
    $connection = new Connection(
        messageHandlerInterface: $handlerSpy,
        messageProcessor: $messageProcessor,
        timeoutHandler: createTimeoutHandler(),
        ip: TEST_LOCAL_IP
    );
    $connection->onMessage(getHandshake());
    $connection->onEnd();

    $handlerSpy->shouldHaveReceived('onDisconnect')->with($connection)->once();
});

test('Should call early desconnection when absent handshake', function () {
    $messageProcessor = createMessageProcessor();
    $messageProcessor->shouldReceive('write')->withAnyArgs();
    $handlerSpy = spy(MessageHandlerInterface::class);
    // Test initialization
    $connection = new Connection(
        messageHandlerInterface: $handlerSpy,
        messageProcessor: $messageProcessor,
        timeoutHandler: createTimeoutHandler(),
        ip: TEST_LOCAL_IP
    );
    $connection->onEnd();

    $handlerSpy->shouldNotHaveReceived('onDisconnect');
});
