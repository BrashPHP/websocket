<?php

namespace Kit\Websocket\Handlers;


use Kit\Websocket\Connection\Connection;
use Kit\Websocket\Connection\TimeoutHandler;
use Kit\Websocket\Events\Protocols\Event;
use Kit\Websocket\Events\Protocols\PromiseListenerInterface;

use Kit\Websocket\Message\Handlers\MessageDeflateProxyHandler;
use Kit\Websocket\Message\Handlers\MessageDeflaterHandler;
use Kit\Websocket\Message\Message;
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
    private readonly MessageDeflateProxyHandler $messageDeflaterHandler;

    public function __construct(
        private readonly TimeoutHandler $timeoutHandler,
        private readonly MessageProcessor $messageProcessor,
        /** @var MessageHandlerInterface[]*/
        private array $messageHandlers = []
    ) {
        $this->messageDeflaterHandler = new MessageDeflateProxyHandler();
    }

    /**
     * @param OnDataReceivedEvent $subject
     *
     * @return PromiseInterface<Connection>
     */
    #[\Override]
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
                foreach ($this->messageHandlers as $handler) {
                    if ($handler->hasSupport($currentMessage)) {
                        if ($conn->isCompressionEnabled()) {
                            $handler = $this->messageDeflaterHandler->proxy($handler);
                        }
                        $handler->handle($currentMessage, connection: $conn);
                    }
                }

                $currentMessage = null;

                continue;
            }
            // Wait for more date before a timeout
            $notifyTimeout->resolve($conn);
        }

        return $notifyTimeout->promise();
    }

    public function addMessageHandler(MessageHandlerInterface $messageHandlerInterface): static
    {
        array_push($this->messageHandlers, $messageHandlerInterface);

        return $this;
    }
}
