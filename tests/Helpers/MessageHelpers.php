<?php

declare(strict_types=1);

namespace Tests\Helpers;

use Evenement\EventEmitterTrait;
use Kit\Websocket\Connection\TimeoutHandler;
use Kit\Websocket\Frame\Enums\FrameTypeEnum;
use Kit\Websocket\Frame\FrameFactory;
use Kit\Websocket\Message\Message;
use Kit\Websocket\Message\MessageProcessor;
use Kit\Websocket\Message\Protocols\MessageHandlerInterface;
use Mockery\LegacyMockInterface;
use Mockery\MockInterface;
use React\Socket\ConnectionInterface;

function createReactConnectionMock(): ConnectionInterface|MockInterface
{
    /** @var ConnectionInterface|\Mockery\MockInterface */
    $mock = mock(ConnectionInterface::class, EventEmitterTrait::class)->makePartial();
    assert($mock instanceof ConnectionInterface);

    return $mock;
}

function mockMessage(FrameTypeEnum $opcode, string $content): LegacyMockInterface|MockInterface|Message
{
    $message = new Message();
    $frameFactory = new FrameFactory();
    $message->addFrame($frameFactory->newFrame($content, $opcode, writeMask: false));

    return $message;
}

function createMessageProcessor(): LegacyMockInterface|MockInterface|MessageProcessor
{
    return mock(MessageProcessor::class);
}

function createTimeoutHandler(): LegacyMockInterface|MockInterface|TimeoutHandler
{
    return mock(TimeoutHandler::class)->makePartial();
}

function createMessageHandler(): LegacyMockInterface|MockInterface|MessageHandlerInterface
{
    return mock(MessageHandlerInterface::class)->makePartial();
}

function getHandshake(): string
{
    return "GET /foo HTTP/1.1\r\n"
        . "Host: 127.0.0.1:8088\r\n"
        . "User-Agent: Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:45.0) Gecko/20100101 Firefox/45.0\r\n"
        . "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8\r\n"
        . "Accept-Language: en-US,en;q=0.5\r\n"
        . "Accept-Encoding: gzip, deflate\r\n"
        . "Sec-WebSocket-Version: 13\r\n"
        . "Origin: null\r\n"
        . "Sec-WebSocket-Extensions: permessage-deflate\r\n"
        . "Sec-WebSocket-Key: nm7Ml8Q7dGJGWWdqnfM7AQ==\r\n"
        . "Connection: keep-alive, Upgrade\r\n"
        . "Pragma: no-cache\r\n"
        . "Cache-Control: no-cache\r\n"
        . "Upgrade: websocket\r\n\r\n";
}
