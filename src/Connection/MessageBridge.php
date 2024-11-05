<?php


namespace Kit\Websocket\Connection;

use Kit\Websocket\Frame\FrameFactory;
use Kit\Websocket\Handlers\OnDataReceivedHandler;
use Kit\Websocket\Message\MessageFactory;
use Kit\Websocket\Message\MessageProcessor;
use Kit\Websocket\Message\MessageWriter;
use Kit\Websocket\Message\Protocols\MessageHandlerInterface;
use React\EventLoop\LoopInterface;
use React\Socket\ConnectionInterface;


final class MessageBridge
{
    public function __construct(
        private LoopInterface $loopInterface,
        private ConnectionInterface $connectionInterface,
        private Config $config
    ) {
    }

    public function create(MessageHandlerInterface $messageHandlerInterface): OnDataReceivedHandler
    {
        return new OnDataReceivedHandler(
            timeoutHandler: new TimeoutHandler(
                loop: $this->loopInterface,
                timeoutSeconds: $this->config->timeout
            ),
            messageProcessor: new MessageProcessor(
                messageWriter: new MessageWriter(
                    frameFactory: new FrameFactory(
                        $this->config->maxPayloadSize
                    ),
                    socket: $this->connectionInterface,
                    writeMasked: $this->config->writeMasked,
                ),
                messageFactory: new MessageFactory(
                    maxMessagesBuffering: $this->config->maxMessagesBuffering
                )
            ),
            messageHandlerInterface: $messageHandlerInterface
        );
    }
}
