<?php

declare(strict_types=1);

namespace Brash\Websocket\Connection;

use Brash\Websocket\Config\Config;
use Brash\Websocket\Frame\FrameFactory;
use Brash\Websocket\Message\MessageWriter;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use React\Socket\ConnectionInterface;

final class ConnectionFactory
{
    public function createConnection(
        ConnectionInterface $connectionInterface,
        LoggerInterface $logger,
        EventDispatcherInterface $eventDispatcher,
        Config $config,
    ): Connection {
        return new Connection(
            eventDispatcher: $eventDispatcher,
            messageWriter: new MessageWriter(
                frameFactory: new FrameFactory(maxPayloadSize: $config->maxPayloadSize),
                socket: $connectionInterface,
                writeMasked: $config->writeMasked
            ),
            ip: $connectionInterface->getRemoteAddress(),
            logger: $logger,
        );
    }
}

