<?php

namespace Tests\Unit\Connection;

use Kit\Websocket\Connection\Connection;
use Kit\Websocket\Events\OnDataReceivedEvent;
use Kit\Websocket\Frame\Enums\CloseFrameEnum;
use Kit\Websocket\Frame\Enums\FrameTypeEnum;
use Kit\Websocket\Handlers\OnDataReceivedHandler;
use Kit\Websocket\Message\Message;
use Kit\Websocket\Message\Protocols\MessageHandlerInterface;
use function Kit\Websocket\functions\hexArrayToString;
use function Tests\Helpers\createMessageProcessor;
use function Tests\Helpers\createTimeoutHandler;
use function Tests\Helpers\mockMessage;
use function Tests\Helpers\readTempZip;

function generateMessage(Message ...$messages)
{
    foreach ($messages as $message) {
        yield $message;
    }
}

test('Should support text message', function (): void {
    $helloFrame = hexArrayToString(['81', '05', '48', '65', '6c', '6c', '6f']);
    // Mock message properties
    $message = mockMessage(FrameTypeEnum::Text, 'Hello');
    $messageProcessor = createMessageProcessor();
    $messageProcessor->shouldReceive('write')->withAnyArgs();
    $messageProcessor->shouldReceive('process')->with($helloFrame, null)->andReturn(generateMessage($message));
    $handlerSpy = spy(MessageHandlerInterface::class);
    // Expectations
    $handlerSpy->shouldReceive('hasSupport')->withAnyArgs()->once()->andReturn(true);

    // Test initialization
    $handler = new OnDataReceivedHandler(
        messageProcessor: $messageProcessor,
        timeoutHandler: createTimeoutHandler(),
        messageHandlers: [$handlerSpy]
    );

    $connection = mock(Connection::class)->makePartial();

    $handler->execute(new OnDataReceivedEvent($helloFrame, $connection));

    $handlerSpy->shouldHaveReceived('handle')->with($message, $connection)->once();
});

test('Should support binary message', function (): void {
    $binary = readTempZip();
    $binaryFrame = hexArrayToString(['82', '7A']) . $binary;

    // Mock message properties
    $message = mockMessage(FrameTypeEnum::Binary, $binary);

    $messageProcessor = createMessageProcessor();

    $messageProcessor->shouldReceive('write')->withAnyArgs();
    $messageProcessor->shouldReceive('process')->with($binaryFrame, null)->andReturn(generateMessage($message));
    $handlerSpy = spy(MessageHandlerInterface::class);
    $handlerSpy->shouldReceive('hasSupport')->withAnyArgs()->once()->andReturn(true);

    $connection = mock(Connection::class)->makePartial();

    // Test initialization
    $handler = new OnDataReceivedHandler(
        messageHandlers: [$handlerSpy],
        messageProcessor: $messageProcessor,
        timeoutHandler: createTimeoutHandler(),

    );
    $handler->execute(new OnDataReceivedEvent($binaryFrame, $connection));

    $handlerSpy->shouldHaveReceived('handle')->with($message, $connection)->once();
});

test('Should handle special messages with handler', function (): void {
    $anonymousHandler = new class () implements MessageHandlerInterface {
        public function hasSupport(Message $message): bool
        {
            return $message->getFirstFrame()->getOpcode() === FrameTypeEnum::Close;
        }

        public function handle(Message $message, Connection $connection): void
        {
            $connection->close(CloseFrameEnum::CLOSE_NORMAL);
        }
    };
    $messageProcessor = createMessageProcessor();
    $messageProcessor->shouldReceive('write')->withAnyArgs();
    $messageProcessor->shouldReceive('process')->with('string', null)->andReturn(generateMessage(
        mockMessage(FrameTypeEnum::Close, '')
    ));

    /**
     * @var Connection|\Mockery\MockInterface
     */
    $connection = mock(Connection::class)->makePartial();
    $connection->expects('close')->with(CloseFrameEnum::CLOSE_NORMAL);
    $handler = new OnDataReceivedHandler(
        messageProcessor: $messageProcessor,
        timeoutHandler: createTimeoutHandler(),
    );
    expect($handler->addMessageHandler($anonymousHandler))->toEqual($handler);

    $handler->execute(new OnDataReceivedEvent('string', $connection))->then(
        fn($conn)=> expect($conn)->toEqual($connection)
    );
});