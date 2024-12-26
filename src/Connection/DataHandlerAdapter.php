<?php

declare(strict_types=1);

namespace Brash\Websocket\Connection;

use Brash\Websocket\Events\Protocols\Event;
use Brash\Websocket\Events\Protocols\PromiseListenerInterface;
use React\Promise\PromiseInterface;


final readonly class DataHandlerAdapter implements PromiseListenerInterface
{
    public function __construct(
        private DataHandlerFactory $dataHandlerFactory
    ) {
    }

    /**
     * @param \Brash\Websocket\Events\OnDataReceivedEvent $subject
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

