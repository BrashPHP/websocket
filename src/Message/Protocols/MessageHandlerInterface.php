<?php

declare(strict_types=1);

namespace Kit\Websocket\Message\Protocols;

use Kit\Websocket\Connection\Connection;
use Kit\Websocket\Message\Message;

interface MessageHandlerInterface
{
    public function handle(Message $message, Connection $connection): void;
    public function hasSupport(Message $message): bool;
}
