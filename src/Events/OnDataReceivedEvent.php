<?php

namespace Brash\Websocket\Events;

use Brash\Websocket\Connection\Connection;
use Brash\Websocket\Events\Protocols\Event;

final class OnDataReceivedEvent extends Event
{
    public function __construct(
        public string $data,
        public Connection $connection
    ) {
    }
}
