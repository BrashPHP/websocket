<?php

declare(strict_types=1);

namespace Brash\Websocket\Connection;

use Brash\Websocket\Config\Config;
use Brash\Websocket\Frame\FrameFactory;
use Brash\Websocket\Message\MessageWriter;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use React\Socket\ConnectionInterface;
use React\Stream\DuplexStreamInterface;

final class ConnectionFactory
{
    public function createConnection(
        DuplexStreamInterface $connectionInterface,
        LoggerInterface $logger,
        EventDispatcherInterface $eventDispatcher,
        Config $config,
        string $ip,
    ): Connection {
        return new Connection(
            eventDispatcher: $eventDispatcher,
            messageWriter: new MessageWriter(
                frameFactory: new FrameFactory(maxPayloadSize: $config->maxPayloadSize),
                socket: $connectionInterface,
                writeMasked: $config->writeMasked
            ),
            ip: $ip,
            logger: $logger,
        );
    }
}

