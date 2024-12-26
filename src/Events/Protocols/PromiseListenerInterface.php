<?php

namespace Brash\Websocket\Events\Protocols;

use React\Promise\PromiseInterface;

/**
 * @template T of Event
 * @template U
 */
interface PromiseListenerInterface
{
    /**
     * @param T $subject
     *
     * @return PromiseInterface<U>
     */
    public function execute(Event $subject): PromiseInterface;
}
