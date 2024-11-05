<?php

namespace Kit\Websocket\Connection\Events;

use Kit\Websocket\Connection\Connection;
use Kit\Websocket\Exceptions\WebSocketException;

interface OnConnectionErrorInterface
{
    public function onError(WebSocketException $e, Connection $connection): void;
}
