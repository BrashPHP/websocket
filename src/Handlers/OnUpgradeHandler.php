<?php

namespace Kit\Websocket\Handlers;


use Kit\Websocket\Events\OnUpgradeEvent;
use Kit\Websocket\Events\Protocols\Event;
use Kit\Websocket\Events\Protocols\ListenerInterface;
use Kit\Websocket\Utilities\HandshakeResponder;
use Kit\Websocket\Utilities\KeyDigest;
use React\Socket\ConnectionInterface;

/**
 * @template-implements ListenerInterface<OnUpgradeEvent>
 */
final class OnUpgradeHandler implements ListenerInterface
{
    public function __construct(private ConnectionInterface $connectionInterface)
    {
    }


    /**
     * Summary of execute
     * @param \Kit\Websocket\Events\OnUpgradeEvent $subject
     *
     * @return void
     */
    public function execute(Event $subject): void
    {
        $request = $subject->request;
        $secWebsocketKeyHeaders = $request->getHeader('sec-websocket-key');
        if (count($secWebsocketKeyHeaders)) {
            $secWebsocketKey = array_pop($secWebsocketKeyHeaders);
            $responder = new HandshakeResponder();
            $keyCreator = new KeyDigest();
            $handshakeResponder = $responder->prepareHandshakeResponse($keyCreator->createSocketAcceptKey($secWebsocketKey));
            $this->connectionInterface->write($handshakeResponder);
        }
    }
}
