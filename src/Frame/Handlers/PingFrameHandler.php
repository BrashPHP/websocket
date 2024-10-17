<?php
/**
 * This file is a part of Woketo package.
 *
 * (c) Nekland <dev@nekland.fr>
 *
 * For the full license, take a look to the LICENSE file
 * on the root directory of this project
 */

namespace Kit\Websocket\Frame\Handlers;


use Kit\Websocket\Frame\Enums\FrameTypeEnum;
use Kit\Websocket\Frame\Protocols\FrameHandlerInterface;
use Kit\Websocket\Message\Message;
use Kit\Websocket\Message\MessageProcessor;
use React\Socket\ConnectionInterface;

class PingFrameHandler implements FrameHandlerInterface
{
    /**
     * {@inheritdoc}
     */
    public function supports(Message $message): bool
    {
        return $message->getFirstFrame()->getOpcode() === FrameTypeEnum::Ping;
    }

    /**
     * {@inheritdoc}
     */
    public function process(
        Message $message,
        MessageProcessor $messageProcessor,
        ConnectionInterface $socket
    ): void {
        $messageProcessor->write($messageProcessor->getFrameFactory()->createPongFrame($message->getContent()));
    }
}
