<?php

namespace Kit\Websocket\Events;

use Kit\Websocket\Events\Protocols\Event;
use Psr\Http\Message\ServerRequestInterface;


class OnUpgradeEvent extends Event
{
    public function __construct(public readonly ServerRequestInterface $request)
    {
    }
}
