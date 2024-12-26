<?php

namespace Brash\Websocket\Connection;

use Brash\Websocket\Config\Config;
use Brash\Websocket\Events\Protocols\PromiseListenerInterface;
use Brash\Websocket\Frame\FrameFactory;
use Brash\Websocket\Handlers\OnDataReceivedHandler;
use Brash\Websocket\Message\MessageFactory;
use Brash\Websocket\Message\MessageProcessor;
use Brash\Websocket\Message\Protocols\MessageHandlerInterface;
use React\EventLoop\LoopInterface;

final class DataHandlerFactory
{
    private readonly TimeoutHandler $cachedtimeoutHandler;
    /**
     * @var \Brash\Websocket\Message\Protocols\MessageHandlerInterface[]
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


