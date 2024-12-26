<?php

declare(strict_types=1);

namespace Brash\Websocket\Message\Handlers;

use Brash\Websocket\Connection\Connection;
use Brash\Websocket\Frame\Enums\FrameTypeEnum;
use Brash\Websocket\Message\Message;
use Brash\Websocket\Message\Protocols\MessageHandlerInterface;

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
