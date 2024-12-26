<?php
namespace Brash\Websocket\Connection\Events;

use Brash\Websocket\Connection\Connection;

interface OnConnectionOpenInterface
{
    public function onOpen(Connection $connection): void;
}
