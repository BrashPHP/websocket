<?php

namespace Brash\Websocket\Handlers\Interfaces;

interface OnSuccessfulHandshakeHandlerInterface
{
    public function act(string $secWebsocketKey): void;
}
