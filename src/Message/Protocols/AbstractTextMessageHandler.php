<?php

namespace Kit\Websocket\Message\Protocols;

use Kit\Websocket\Connection\Connection;
use Kit\Websocket\Frame\Enums\FrameTypeEnum;
use React\Promise\Promise;

abstract class AbstractTextMessageHandler implements MessageHandlerInterface
{
    public function supportsFrame(FrameTypeEnum $opcode): bool{
        return $opcode === FrameTypeEnum::Text;
    }

    abstract public function handleTextData(string $data, Connection $connection): void;

    public function handle(string $data, Connection $connection): void
    {
        $this->handleTextData($data, $connection);
    }
}

