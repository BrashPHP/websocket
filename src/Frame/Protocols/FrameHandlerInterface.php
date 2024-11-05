<?php

declare(strict_types=1);

namespace Kit\Websocket\Frame\Protocols;

use Kit\Websocket\Message\Message;
use Kit\Websocket\Message\MessageWriter;

interface FrameHandlerInterface
{
    public function supports(Message $message): bool;
    public function process(Message $message, MessageWriter $messageWriter): void;
}
