<?php

declare(strict_types=1);

namespace Tests\Unit\Frame;

use Kit\Websocket\Frame\Enums\CloseFrameEnum;
use Kit\Websocket\Frame\FrameFactory;
use Kit\Websocket\Message\Message;

use function Kit\Websocket\functions\hexArrayToString;

test('Should create close frame', function () {
    $factory = new FrameFactory();
    $frame = $factory->createCloseFrame(CloseFrameEnum::CLOSE_NORMAL);

    expect($frame->getRawData())->toEqual(hexArrayToString(['88', '02', '03', 'E8']));
});

test('Should create pong frame', function () {
    $pingMessage = new Message();
    $hexStr = hexArrayToString(['89', '00']);
    $frameBuilt = (new FrameFactory(maxPayloadSize: 50000))->newFrameFromRawData($hexStr);
    $pingMessage->addFrame($frameBuilt);
    $factory = new FrameFactory();
    $message = $pingMessage->getContent();

    $frame = $factory->createPongFrame($message);

    expect($frame->getPayload())->toEqual($message);
});

