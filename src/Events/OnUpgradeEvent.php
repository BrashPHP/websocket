<?php

namespace Kit\Websocket\Events;

use Kit\Websocket\Events\Protocols\Event;
use Psr\Http\Message\RequestInterface;

class OnUpgradeEvent extends Event
{
    public function __construct(public readonly RequestInterface $request)
    {
    }
}
