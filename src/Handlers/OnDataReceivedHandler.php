<?php

namespace Kit\Websocket\Handlers;


use Kit\Websocket\Events\OnUpgradeEvent;
use Kit\Websocket\Events\Protocols\Event;
use Kit\Websocket\Events\Protocols\ListenerInterface;
use Kit\Websocket\Utilities\HandshakeResponder;
use Kit\Websocket\Utilities\KeyDigest;
use React\Socket\ConnectionInterface;
use React\Stream\WritableResourceStream;

use function Kit\Websocket\functions\println;
/**
 * @template-implements ListenerInterface<OnUpgradeEvent>
 */
final class OnDataReceivedHandler implements ListenerInterface
{
    private bool $isFirstChunk = false;
    private string $firstChunk = '';

    public function __construct(private ConnectionInterface $connectionInterface)
    {
    }


    /**
     * Summary of execute
     * @param \Kit\Websocket\Events\OnDataReceivedEvent $subject
     *
     * @return void
     */
    public function execute(Event $subject): void
    {
        $readableStream = $subject->readableStreamInterface;
        $readableStream->on('data', $this->handleMessage(...));
        $readableStream->on('end', $this->reset(...));
    }

    private function handleMessage(string $chunk): void
    {
        if ($this->isFirstChunk) {
            
        }
    }

    private function reset(){
        $this->isFirstChunk = false;
    }
}
