<?php

namespace Kit\Websocket\Events\Protocols;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\EventDispatcher\StoppableEventInterface;
use React\Promise\PromiseInterface;
use function React\Promise\resolve;

class PromiseEventDispatcher implements EventDispatcherInterface
{

    public function __construct(private readonly ListenerProviderInterface $listenerProvider)
    {
    }

    #[\Override]
    public function dispatch(object $event): PromiseInterface
    {

        if ($event instanceof StoppableEventInterface && $event->isPropagationStopped()) {
            return resolve($event);
        }

        foreach ($this->listenerProvider->getListenersForEvent($event) as $listener) {
            $promiseEvent = null;
            if ($listener instanceof ListenerInterface) {
                $listener->execute($event);
            } elseif ($listener instanceof PromiseListenerInterface) {
                $promiseEvent = $listener->execute($event);
            } elseif (is_callable($listener)) {
                $res = $listener($event);
                if ($res instanceof PromiseInterface) {
                    $promiseEvent = $res;
                }
            }
        }

        return $promiseEvent ?? resolve($event);
    }
}
