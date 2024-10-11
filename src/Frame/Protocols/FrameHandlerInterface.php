<?php

declare(strict_types=1);

namespace Kit\Websocket\Frame\Protocols;

use Kit\Websocket\Message\Message;
use Kit\Websocket\Message\MessageProcessor;
use React\Socket\ConnectionInterface;

interface FrameHandlerInterface
{
    public function supports(Message $message): bool;
    public function process(Message $message, MessageProcessor $messageProcessor, ConnectionInterface $socket): void;
}
