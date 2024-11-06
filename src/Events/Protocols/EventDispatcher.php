<?php

namespace Kit\Websocket\Events\Protocols;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\EventDispatcher\StoppableEventInterface;

class EventDispatcher implements EventDispatcherInterface
{

    public function __construct(private readonly ListenerProviderInterface $listenerProvider)
    {
    }

    #[\Override]
    public function dispatch(object $event): object
    {

        if ($event instanceof StoppableEventInterface && $event->isPropagationStopped()) {
            return $event;
        }

        foreach ($this->listenerProvider->getListenersForEvent($event) as $listener) {
            if ($listener instanceof ListenerInterface) {
                $listener->execute($event);
            } elseif (is_callable($listener)) {
                $listener($event);
            }
        }

        return $event;
    }
}
