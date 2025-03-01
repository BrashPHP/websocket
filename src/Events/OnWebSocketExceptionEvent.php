<?php

declare(strict_types=1);

namespace Brash\Websocket\Events;

use Brash\Websocket\Connection\Connection;
use Brash\Websocket\Events\Protocols\Event;
use Brash\Websocket\Exceptions\WebSocketException;

class OnWebSocketExceptionEvent extends Event
{
    public function __construct(public WebSocketException $webSocketException, public Connection $connection)
    {
    }
}
