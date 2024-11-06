<?php

namespace Kit\Websocket\Events;

use Kit\Websocket\Connection\Connection;
use Kit\Websocket\Events\Protocols\Event;

final class OnDataReceivedEvent extends Event
{
    public function __construct(
        public string $data,
        public Connection $connection
    ) {
    }
}
