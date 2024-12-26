<?php

declare(strict_types=1);

namespace Brash\Websocket\Message\Protocols;

use Brash\Websocket\Connection\Connection;
use Brash\Websocket\Message\Message;

interface MessageHandlerInterface
{
    public function handle(Message $message, Connection $connection): void;
    public function hasSupport(Message $message): bool;
}
