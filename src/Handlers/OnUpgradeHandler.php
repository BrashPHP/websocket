<?php

namespace Brash\Websocket\Handlers;

use Brash\Websocket\Events\OnUpgradeEvent;
use Brash\Websocket\Events\Protocols\Event;
use Brash\Websocket\Events\Protocols\PromiseListenerInterface;
use Brash\Websocket\Http\RequestVerifier;
use Brash\Websocket\Utilities\KeyDigest;
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
