<?php

declare(strict_types=1);

namespace Kit\Websocket\Events;

use Kit\Websocket\Connection\Connection;
use Kit\Websocket\Events\Protocols\Event;
use Kit\Websocket\Exceptions\WebSocketException;

class OnWebSocketExceptionEvent extends Event
{
    public function __construct(public WebSocketException $webSocketException, public Connection $connection)
    {
    }
}
