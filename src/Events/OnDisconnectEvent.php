<?php

declare(strict_types=1);

namespace Brash\Websocket\Events;

use Brash\Websocket\Connection\Connection;
use Brash\Websocket\Events\Protocols\Event;

final class OnDisconnectEvent extends Event
{
    public function __construct(public Connection $connection)
    {
    }
}
