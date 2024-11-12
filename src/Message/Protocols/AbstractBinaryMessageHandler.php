<?php

namespace Kit\Websocket\Message\Protocols;

use Kit\Websocket\Connection\Connection;
use Kit\Websocket\Connection\Events\OnConnectionErrorInterface;
use Kit\Websocket\Connection\Events\OnConnectionOpenInterface;
use Kit\Websocket\Connection\Events\OnDisconnecedConnectiontInterface;
use Kit\Websocket\Exceptions\WebSocketException;
use Kit\Websocket\Frame\Enums\FrameTypeEnum;
use Kit\Websocket\Message\Message;

abstract class AbstractBinaryMessageHandler implements ConnectionHandlerInterface
{
    #[\Override]
    public function hasSupport(Message $message): bool
    {
        return $message->getOpcode() === FrameTypeEnum::Binary;
    }

    abstract public function handleBinaryData(string $data, Connection $connection): void;

    #[\Override]
    public function handle(Message $message, Connection $connection): void
    {
        $this->handleBinaryData($message->getContent(), $connection);
    }

    #[\Override]
    public function onDisconnect(Connection $connection): void
    {

    }

    #[\Override]
    public function onOpen(Connection $connection): void
    {

    }

    #[\Override]
    public function onError(WebSocketException $e, Connection $connection): void
    {
    }

}

