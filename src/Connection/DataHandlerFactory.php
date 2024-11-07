<?php

namespace Kit\Websocket\Connection;

use Kit\Websocket\Events\Protocols\PromiseListenerInterface;
use Kit\Websocket\Frame\FrameFactory;
use Kit\Websocket\Handlers\OnDataReceivedHandler;
use Kit\Websocket\Message\MessageFactory;
use Kit\Websocket\Message\MessageProcessor;
use Kit\Websocket\Message\Protocols\MessageHandlerInterface;
use React\EventLoop\LoopInterface;

final class DataHandlerFactory
{
    private readonly TimeoutHandler $cachedtimeoutHandler;
    /**
     * @var \Kit\Websocket\Message\Protocols\MessageHandlerInterface[]
     */
    private array $messageHandlers = [];

    public function __construct(
        private readonly Config $config,
        private readonly LoopInterface $loopInterface
    ) {
        $this->cachedtimeoutHandler = new TimeoutHandler(
            loop: $this->loopInterface,
            timeoutSeconds: $this->config->timeout
        );
    }

    public function appendMessageHandler(MessageHandlerInterface $messageHandlerInterface)
    {
        $this->messageHandlers[] = $messageHandlerInterface;
    }

    public function create(): PromiseListenerInterface
    {
        $handler = new OnDataReceivedHandler(
            timeoutHandler: $this->cachedtimeoutHandler,
            messageProcessor: $this->createMessageProcessor(),
        );

        foreach ($this->messageHandlers as $messageHandler) {
            $handler->addMessageHandler($messageHandler);
        }

        return $handler;
    }

    private function createMessageProcessor(): MessageProcessor
    {
        return new MessageProcessor(
            messageFactory: new MessageFactory(
                maxMessagesBuffering: $this->config->maxMessagesBuffering
            ),
            frameFactory: new FrameFactory($this->config->maxPayloadSize),
        );
    }
}


