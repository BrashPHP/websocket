<?php

namespace Brash\Websocket\Events\Protocols;

/**
 * @template T of Event
 */
interface ListenerInterface
{
    /**
     * @param T $subject
     */
    public function execute(Event $subject): void;
}
