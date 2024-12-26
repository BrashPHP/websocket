<?php

declare(strict_types=1);

namespace Brash\Websocket\Events;

use Brash\Websocket\Connection\Connection;
use Brash\Websocket\Events\Protocols\Event;

class OnNewConnectionOpenEvent extends Event
{
    public function __construct(public Connection $connection)
    {
    }
}
