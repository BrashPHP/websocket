<?php

namespace Tests\Unit\Message\Handlers;

use Kit\Websocket\Connection\Connection;
use Kit\Websocket\Frame\FrameFactory;
use Kit\Websocket\Message\Handlers\PingFrameHandler;
use Kit\Websocket\Message\Message;
use Kit\Websocket\Message\MessageWriter;
use function Kit\Websocket\functions\hexArrayToString;


test('Should process close frame', function () {
    $handler = new PingFrameHandler();
    $pingMessage = new Message();
    $factory = new FrameFactory();
    $pingMessage->addFrame($factory->newFrameFromRawData(hexArrayToString(['89', '00'])));
    expect($handler->hasSupport($pingMessage))->toBeTrue();
});

test('Should process ping frame', function () {
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

