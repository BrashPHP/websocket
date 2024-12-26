<?php

namespace Brash\Websocket\Message\Protocols;

use Brash\Websocket\Connection\Connection;
use Brash\Websocket\Connection\Events\OnConnectionErrorInterface;
use Brash\Websocket\Connection\Events\OnConnectionOpenInterface;
use Brash\Websocket\Connection\Events\OnDisconnecedConnectiontInterface;
use Brash\Websocket\Exceptions\WebSocketException;
use Brash\Websocket\Frame\Enums\FrameTypeEnum;
use Brash\Websocket\Message\Message;

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

