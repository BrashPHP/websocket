<?php

namespace Kit\Websocket\Message\Protocols;

use Kit\Websocket\Connection\Connection;
use Kit\Websocket\Frame\Enums\FrameTypeEnum;
use Kit\Websocket\Message\Message;

abstract class AbstractTextMessageHandler implements MessageHandlerInterface
{
    #[\Override]
    public function hasSupport(Message $message): bool{
        return $message->getOpcode() === FrameTypeEnum::Text;
    }

    abstract public function handleTextData(string $data, Connection $connection): void;

    #[\Override]
    public function handle(Message $message, Connection $connection): void
    {
        $this->handleTextData($message->getContent(), $connection);
    }
}

