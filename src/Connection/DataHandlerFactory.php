<?php

namespace Kit\Websocket\Connection;

use Kit\Websocket\Events\Protocols\PromiseListenerInterface;
use Kit\Websocket\Handlers\OnDataReceivedHandler;
use Kit\Websocket\Message\MessageFactory;
use Kit\Websocket\Message\MessageProcessor;
use Kit\Websocket\Message\Protocols\MessageHandlerInterface;
use React\EventLoop\LoopInterface;

final class DataHandlerFactory
{
    private TimeoutHandler $cachedtimeoutHandler;
    /**
     * @var \Kit\Websocket\Message\Protocols\MessageHandlerInterface[]
     */
    private array $messageHandlers = [];

    public function __construct(
        private Config $config,
        private LoopInterface $loopInterface
    ) {
        $this->cachedtimeoutHandler = new TimeoutHandler(
            loop: $this->loopInterface,
            timeoutSeconds: $this->config->timeout
        );
    }

    public function appendMessageHandler(MessageHandlerInterface $messageHandlerInterface){
        $this->messageHandlers[] = $messageHandlerInterface;
    }

    public function create(Connection $connection): PromiseListenerInterface
    {
        $handler = new OnDataReceivedHandler(
            timeoutHandler: $this->cachedtimeoutHandler,
            messageProcessor: $this->createMessageProcessor(
                $connection
            ),
        );

        foreach ($this->messageHandlers as $messageHandler) {
            $handler->addMessageHandler($messageHandler);
        }

        return $handler;
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


