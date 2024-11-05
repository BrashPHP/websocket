<?php

namespace Kit\Websocket\Connection\Events;

use Kit\Websocket\Connection\Connection;

interface OnDisconnecedConnectiontInterface
{
    public function onDisconnect(Connection $connection): void;
}
