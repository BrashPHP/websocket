<?php
namespace Kit\Websocket\Connection\Events;

use Kit\Websocket\Connection\Connection;

interface OnConnectionOpenInterface
{
    public function onOpen(Connection $connection): void;
}
