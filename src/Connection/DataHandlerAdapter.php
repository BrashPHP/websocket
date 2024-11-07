<?php

declare(strict_types=1);

namespace Kit\Websocket\Connection;

use Kit\Websocket\Events\Protocols\Event;
use Kit\Websocket\Events\Protocols\PromiseListenerInterface;
use React\Promise\PromiseInterface;


final readonly class DataHandlerAdapter implements PromiseListenerInterface
{
    public function __construct(
        private DataHandlerFactory $dataHandlerFactory
    ) {
    }

    /**
     * @param \Kit\Websocket\Events\OnDataReceivedEvent $subject
     *
     * @return PromiseInterface<Connection>
     */
    #[\Override]
    public function execute(Event $subject): PromiseInterface
    {
        $handler = $this->dataHandlerFactory->create();

        return $handler->execute($subject);
    }
}

