<?php

namespace Brash\Websocket\Connection\Events;

use Brash\Websocket\Connection\Connection;
use Brash\Websocket\Exceptions\WebSocketException;

interface OnConnectionErrorInterface
{
    public function onError(WebSocketException $e, Connection $connection): void;
}
