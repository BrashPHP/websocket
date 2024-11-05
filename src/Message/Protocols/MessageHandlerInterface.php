<?php

declare(strict_types=1);

namespace Kit\Websocket\Message\Protocols;

use Kit\Websocket\Connection\Connection;
use Kit\Websocket\Frame\Enums\FrameTypeEnum;

interface MessageHandlerInterface
{
    public function handle(string $data, Connection $connection): void;
    public function supportsFrame(FrameTypeEnum $opcode): bool;
}
