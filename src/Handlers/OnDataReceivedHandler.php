<?php

namespace Kit\Websocket\Handlers;


use Kit\Websocket\Connection\Connection;
use Kit\Websocket\Connection\TimeoutHandler;
use Kit\Websocket\Events\Protocols\Event;
use Kit\Websocket\Events\Protocols\PromiseListenerInterface;

use Kit\Websocket\Message\MessageProcessor;
use Kit\Websocket\Message\Protocols\MessageHandlerInterface;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use Kit\Websocket\Events\OnDataReceivedEvent;

/**
 * @template-implements PromiseListenerInterface<OnDataReceivedEvent,Connection>
 */
final class OnDataReceivedHandler implements PromiseListenerInterface
{
    public function __construct(
        private TimeoutHandler $timeoutHandler,
        private MessageProcessor $messageProcessor,
        private MessageHandlerInterface $messageHandlerInterface
    ) {
    }

    /**
     * Summary of execute
     * @param OnDataReceivedEvent $subject
     *
     * @return PromiseInterface<Connection>
     */
    public function execute(Event $subject): PromiseInterface
    {
        $data = $subject->data;
        $conn = $subject->connection;
        $currentMessage = null;
        $notifyTimeout = new Deferred();
        $this->timeoutHandler->handleConnectionTimeout($notifyTimeout->promise());

        foreach ($this->messageProcessor->process(data: $data, unfinishedMessage: $currentMessage) as $message) {
            $currentMessage = $message;
            if ($currentMessage->isComplete()) {
                if ($this->messageHandlerInterface->supportsFrame(opcode: $currentMessage->getOpcode())) {
                    $content = $currentMessage->getContent();
                    $this->messageHandlerInterface->handle(data: $content, connection: $conn);
                }
                $currentMessage = null;

                continue;
            }
            // Wait for more date before a timeout
            $notifyTimeout->resolve($conn);
        }

        return $notifyTimeout->promise();
    }
}
