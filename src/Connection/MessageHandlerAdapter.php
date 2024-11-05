<?php

declare(strict_types=1);

namespace Kit\Websocket\Connection;

use Kit\Websocket\Events\Protocols\Event;
use Kit\Websocket\Events\Protocols\PromiseListenerInterface;
use Kit\Websocket\Handlers\OnDataReceivedHandler;
use Kit\Websocket\Message\MessageFactory;
use Kit\Websocket\Message\MessageProcessor;
use Kit\Websocket\Message\Protocols\MessageHandlerInterface;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;


final class MessageHandlerAdapter implements PromiseListenerInterface
{
    private TimeoutHandler $cachedtimeoutHandler;

    public function __construct(
        private MessageHandlerInterface $messageHandlerInterface,
        private Config $config,
        private LoopInterface $loopInterface
    ) {
        $this->cachedtimeoutHandler = new TimeoutHandler(
            loop: $this->loopInterface,
            timeoutSeconds: $this->config->timeout
        );
    }

    /**
     * @param \Kit\Websocket\Events\OnDataReceivedEvent $subject
     *
     * @return PromiseInterface<Connection>
     */
    public function execute(Event $subject): PromiseInterface
    {
        $handler = new OnDataReceivedHandler(
            timeoutHandler: $this->cachedtimeoutHandler,
            messageProcessor: $this->createMessageProcessor($subject->connection),
            messageHandlerInterface: $this->messageHandlerInterface
        );

        return $handler->execute($subject);
    }

    private function createMessageProcessor(Connection $connection)
    {
        return new MessageProcessor(
            messageWriter: $connection->getSocketWriter(),
            messageFactory: new MessageFactory(
                maxMessagesBuffering: $this->config->maxMessagesBuffering
            )
        );
    }
}

