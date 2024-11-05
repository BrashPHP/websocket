<?php

namespace Kit\Websocket\Handlers;

use Kit\Websocket\Events\OnUpgradeEvent;
use Kit\Websocket\Events\Protocols\Event;
use Kit\Websocket\Events\Protocols\PromiseListenerInterface;
use Kit\Websocket\Http\RequestVerifier;
use Kit\Websocket\Utilities\HandshakeResponder;
use Kit\Websocket\Utilities\KeyDigest;
use React\Promise\Promise;
use React\Promise\PromiseInterface;

final class OnUpgradeHandler implements PromiseListenerInterface
{
    private RequestVerifier $requestVerifier;
    private HandshakeResponder $handshakeResponder;
    private KeyDigest $keyDigest;

    public function __construct()
    {
        $this->requestVerifier = new RequestVerifier();
        $this->handshakeResponder = new HandshakeResponder();
        $this->keyDigest = new KeyDigest();
    }

    /**
     * @param \Kit\Websocket\Events\OnUpgradeEvent $event
     *
     * @return \React\Promise\PromiseInterface
     */
    public function execute(Event $event): PromiseInterface
    {
        $request = $event->request;
        return new Promise(
            resolver: function (callable $resolve, callable $reject) use ($request) {
                $result = $this->requestVerifier->verify($request);
                if (is_null($result)) {
                    $secWebsocketKeyHeaders = $request->getHeader('sec-websocket-key');
                    if (count($secWebsocketKeyHeaders)) {
                        $secWebsocketKey = array_pop($secWebsocketKeyHeaders);

                        $handshakeResponse = $this->handshakeResponder->prepareHandshakeResponse(
                            acceptKey: $this->keyDigest->createSocketAcceptKey($secWebsocketKey)
                        );

                        return $resolve($handshakeResponse);
                    }
                }

                return $reject($result);
            }
        );
    }
}
