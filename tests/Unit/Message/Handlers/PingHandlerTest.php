<?php

namespace Tests\Unit\Message\Handlers;

use Brash\Websocket\Connection\Connection;
use Brash\Websocket\Frame\FrameFactory;
use Brash\Websocket\Message\Handlers\PingFrameHandler;
use Brash\Websocket\Message\Message;
use Brash\Websocket\Message\MessageWriter;
use function Brash\Websocket\functions\hexArrayToString;


test('Should process close frame', function (): void {
    $handler = new PingFrameHandler();
    $pingMessage = new Message();
    $factory = new FrameFactory();
    $pingMessage->addFrame($factory->newFrameFromRawData(hexArrayToString(['89', '00'])));
    expect($handler->hasSupport($pingMessage))->toBeTrue();
});

test('Should process ping frame', function (): void {
    $handler = new PingFrameHandler();
    $pingMessage = new Message();
    $factory = new FrameFactory();
    $framePing=$factory->newFrameFromRawData(hexArrayToString(['89', '7F', '00', '00', '00', '00', '00', '00', '00', '05', '48', '65', '6c', '6c', '6f']));
    $pingMessage->addFrame($framePing);
    $conn = mock(Connection::class);
    $socketWriter = mock(MessageWriter::class);
    $conn->shouldReceive('getSocketWriter')->andReturn($socketWriter);
    $socketWriter->shouldReceive('getFrameFactory')->andReturn($factory);
    $socketWriter->shouldReceive('writeTextFrame')->andReturn($factory->createPongFrame($framePing->getContent()));
    $handler->handle($pingMessage, $conn);
    expect($handler->hasSupport($pingMessage))->toBeTrue();
});

