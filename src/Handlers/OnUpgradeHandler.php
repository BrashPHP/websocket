<?php

namespace Kit\Websocket\Handlers;

use Kit\Websocket\Http\RequestVerifier;
use Kit\Websocket\Utilities\HandshakeResponder;
use Kit\Websocket\Utilities\KeyDigest;
use Psr\Http\Message\RequestInterface;
use React\Promise\Promise;

final class OnUpgradeHandler
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

    public function execute(RequestInterface $request): Promise
    {
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
