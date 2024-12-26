<?php

namespace Tests\Unit\Message\Handlers;

use Brash\Websocket\Connection\Connection;
use Brash\Websocket\Frame\Enums\CloseFrameEnum;
use Brash\Websocket\Frame\FrameFactory;
use Brash\Websocket\Message\Handlers\CloseFrameHandler;
use Brash\Websocket\Message\Message;
use function Brash\Websocket\functions\hexArrayToString;
use function Brash\Websocket\functions\intToBinaryString;
use function Tests\Unit\Connection\createConnectionObject;

function createSut(): CloseFrameHandler
{
    return new CloseFrameHandler();
}


function createMessage(): Message
{
    return new Message();
}

test('Should process close frame', function (): void {
    $sut = createSut();
    $factory = new FrameFactory();
    $closeFrame = $factory->createCloseFrame();
    $message = new Message();
    $message->addFrame($closeFrame);
    expect($sut->hasSupport($message))->toBeTrue();
    $conn = createConnectionObject();
    $sut->handle($message, $conn);
    $conn->shouldHaveReceived('close')->with(CloseFrameEnum::CLOSE_NORMAL);
});

test('Should process close frame from raw data', function (): void {
    $sut = createSut();
    $factory = new FrameFactory();
    $closeFrame = $factory->newFrameFromRawData(rawData: hexArrayToString(['88', '02', '03', 'E8']));
    $message = new Message();
    $message->addFrame($closeFrame);
    expect($sut->hasSupport($message))->toBeTrue();
    $conn = createConnectionObject();
    $sut->handle($message, $conn);
    $conn->shouldHaveReceived('close')->with(CloseFrameEnum::CLOSE_NORMAL);
});

test('Should close with protocol error when frame is not valid', function (): void {
    $frameFactory = new FrameFactory();

    // Normal close frame without mask
    $message = new Message();
    $message->addFrame($frameFactory->newFrameFromRawData(hexArrayToString(['F8', '02', '03', 'E8'])));

    $handler = new CloseFrameHandler();
    expect($handler->hasSupport($message))->toBeTrue();
    $conn = createConnectionObject();
    $handler->handle($message, $conn);

    $conn->shouldHaveReceived('close')->with(CloseFrameEnum::CLOSE_PROTOCOL_ERROR);
});

test('Should close with protocol error on wrong close code', function ($codeFrameIn, $codeFrameOut): void {
    $frameIn = intToBinaryString($codeFrameIn, 2);
    $frameFactory = new FrameFactory();
    $frameFactory->createCloseFrame();
    $message = new Message();
    $message->addFrame($frameFactory->newFrameFromRawData(hexArrayToString(['88', '02']) . $frameIn));
    $handler = new CloseFrameHandler();
    $conn = mock(Connection::class);
    $conn->shouldReceive('close')->with($codeFrameOut);
    expect($handler->hasSupport($message))->toBeTrue();
    $handler->handle($message, $conn);
})
    ->with(
        [
            [999, CloseFrameEnum::CLOSE_PROTOCOL_ERROR],
            [10, CloseFrameEnum::CLOSE_PROTOCOL_ERROR],
            [1000, CloseFrameEnum::CLOSE_NORMAL],
            [1100, CloseFrameEnum::CLOSE_PROTOCOL_ERROR],
            [4000, CloseFrameEnum::CLOSE_PROTOCOL_ERROR],
            [6000, CloseFrameEnum::CLOSE_PROTOCOL_ERROR],
        ]
    );

