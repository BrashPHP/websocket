<?php

namespace Brash\Websocket\Connection\Events;

use Brash\Websocket\Connection\Connection;

interface OnDisconnecedConnectiontInterface
{
    public function onDisconnect(Connection $connection): void;
}
