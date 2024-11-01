<?php

declare(strict_types=1);

namespace Kit\Websocket\Message\Protocols;

use Kit\Websocket\Connection\Connection;
use Kit\Websocket\Exceptions\WebSocketException;
use Kit\Websocket\Frame\Enums\FrameTypeEnum;
use React\Promise\Promise;

interface MessageHandlerInterface
{
    public function onOpen(Connection $connection): void;
    public function handle(string $data, Connection $connection): Promise;
    public function supportsFrame(FrameTypeEnum $opcode): bool;
    public function onError(WebSocketException $e, Connection $connection): void;
    public function onDisconnect(Connection $connection): void;
}
