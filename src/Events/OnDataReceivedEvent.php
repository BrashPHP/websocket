<?php

namespace Kit\Websocket\Events;

use Kit\Websocket\Events\Protocols\Event;
use React\Stream\ReadableStreamInterface;

final class OnDataReceivedEvent extends Event
{
    public function __construct(public readonly ReadableStreamInterface $readableStreamInterface)
    {
    }
}
