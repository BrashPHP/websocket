<?php

namespace Brash\Websocket\Message\Protocols;

use Brash\Websocket\Connection\Connection;
use Brash\Websocket\Exceptions\WebSocketException;
use Brash\Websocket\Frame\Enums\FrameTypeEnum;
use Brash\Websocket\Message\Message;

abstract class AbstractTextMessageHandler implements ConnectionHandlerInterface
{
    #[\Override]
    public function hasSupport(Message $message): bool
    {
        return $message->getOpcode() === FrameTypeEnum::Text;
    }

    abstract public function handleTextData(string $data, Connection $connection): void;

    #[\Override]
    public function handle(Message $message, Connection $connection): void
    {
        $this->handleTextData($message->getContent(), $connection);
    }

    #[\Override]
    public function onDisconnect(Connection $connection): void
    {
        $connection->writeText('Disconnected');
        $connection->getLogger()->info("New Connection removed!") ;

    }

    #[\Override]
    public function onOpen(Connection $connection): void
    {
        $connection->getLogger()->info("New Connection added!") ;
    }

    #[\Override]
    public function onError(WebSocketException $e, Connection $connection): void
    {
    }
}

