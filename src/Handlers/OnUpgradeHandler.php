<?php

namespace Kit\Websocket\Handlers;

use Kit\Websocket\Events\OnUpgradeEvent;
use Kit\Websocket\Events\Protocols\Event;
use Kit\Websocket\Events\Protocols\PromiseListenerInterface;
use Kit\Websocket\Http\RequestVerifier;
use Kit\Websocket\Utilities\KeyDigest;
use React\Promise\Promise;
use React\Promise\PromiseInterface;

final readonly class OnUpgradeHandler implements PromiseListenerInterface
{
    private RequestVerifier $requestVerifier;
    private KeyDigest $keyDigest;

    public function __construct()
    {
        $this->requestVerifier = new RequestVerifier();

        $this->keyDigest = new KeyDigest();
    }

    /**
     * @param OnUpgradeEvent $event
     *
     * @return \React\Promise\PromiseInterface
     */
    #[\Override]
    public function execute(Event $event): PromiseInterface
    {
        $request = $event->request;
        return new Promise(
            resolver: function (callable $resolve, callable $reject) use ($request) {
                $result = $this->requestVerifier->verify($request);
                if ($result === null) {
                    $secWebsocketKeyHeaders = $request->getHeader('sec-websocket-key');
                    if (count($secWebsocketKeyHeaders)) {
                        $secWebsocketKey = array_pop($secWebsocketKeyHeaders);

                        $acceptKey = $this->keyDigest->createSocketAcceptKey($secWebsocketKey);

                        return $resolve($acceptKey);
                    }
                }

                return $reject($result);
            }
        );
    }
}
