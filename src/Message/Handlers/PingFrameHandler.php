<?php

declare(strict_types=1);

namespace Kit\Websocket\Message\Handlers;

use Kit\Websocket\Connection\Connection;
use Kit\Websocket\Frame\Enums\FrameTypeEnum;
use Kit\Websocket\Message\Message;
use Kit\Websocket\Message\Protocols\MessageHandlerInterface;

class PingFrameHandler implements MessageHandlerInterface
{
    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function hasSupport(Message $message): bool
    {
        return $message->getOpcode() === FrameTypeEnum::Ping;
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function handle(Message $message, Connection $connection): void
    {
        $socketWriter = $connection->getSocketWriter();
        $pong = $socketWriter->getFrameFactory()->createPongFrame($message->getContent());
        $socketWriter->writeTextFrame($pong);
    }
}
