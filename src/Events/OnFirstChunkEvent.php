<?php
namespace Kit\Websocket\Events;
use Kit\Websocket\Events\Protocols\Event;

final class OnFirstChunkEvent extends Event
{
    public function __construct(public readonly string $chunk)
    {
    }
}
