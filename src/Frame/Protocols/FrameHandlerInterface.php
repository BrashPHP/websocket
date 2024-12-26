<?php

declare(strict_types=1);

namespace Brash\Websocket\Frame\Protocols;

use Brash\Websocket\Message\Message;
use Brash\Websocket\Message\MessageWriter;

interface FrameHandlerInterface
{
    public function supports(Message $message): bool;
    public function process(Message $message, MessageWriter $messageWriter): void;
}
