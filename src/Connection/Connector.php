<?php

declare(strict_types=1);

namespace Brash\Websocket\Connection;

use React\Socket\ConnectionInterface;

final readonly class Connector
{
    public function __construct(
        private ConnectionInterface $socketStream,
    ) {
    }
    public function connect(Connection $connection)
    {
        $this->socketStream->on('data', $connection->onMessage(...));
        $this->socketStream->once('end', $connection->onEnd(...));
        $this->socketStream->on('error', $connection->onError(...));
    }
}
