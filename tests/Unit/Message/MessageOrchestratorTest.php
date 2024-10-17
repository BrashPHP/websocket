<?php


namespace Tests\Unit\Message;
use Kit\Websocket\Frame\FrameFactory;
use Kit\Websocket\Message\Message;
use Kit\Websocket\Message\MessageBus;
use Kit\Websocket\Message\MessageOrchestrator;
use function Kit\Websocket\functions\hexArrayToString;

test('Should build message correctly', function ()  {
    $processor = new MessageOrchestrator(new FrameFactory());
    $data = hexArrayToString(['81', '05', '48', '65', '6c', '6c', '6f']);
    $messageBus = new MessageBus($data);
    $message = $processor->onData(
        // Hello normal frame
        $messageBus,
        new Message()
    );

    expect($message)->toBeInstanceOf(Message::class);
    expect($message->getContent())->toEqual('Hello');
});
