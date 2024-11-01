<?php

namespace Kit\Websocket\Message\Protocols;

use Kit\Websocket\Connection\Connection;
use Kit\Websocket\Frame\Enums\FrameTypeEnum;
use React\Promise\Deferred;
use React\Promise\Promise;

abstract class AbstractBinaryMessageHandler implements MessageHandlerInterface
{
    public function supportsFrame(FrameTypeEnum $opcode): bool
    {
        return $opcode === FrameTypeEnum::Binary;
    }

    abstract public function handleBinaryData(string $data, Connection $connection): void;

    public function handle(string $data, Connection $connection): Promise
    {
        return new Promise(fn($resolve) => $resolve(
            $this->handleBinaryData($data, $connection)
        ));
    }
}

