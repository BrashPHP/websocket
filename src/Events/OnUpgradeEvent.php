<?php

namespace Brash\Websocket\Events;

use Brash\Websocket\Connection\Connection;
use Brash\Websocket\Events\Protocols\Event;
use Psr\Http\Message\RequestInterface;

class OnUpgradeEvent extends Event
{
    public function __construct(public readonly RequestInterface $request, public readonly Connection $connection)
    {
    }
}
