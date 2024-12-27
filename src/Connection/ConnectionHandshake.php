<?php

namespace Brash\Websocket\Connection;

use Brash\Websocket\Exceptions\WebSocketException;
use Brash\Websocket\Http\Exceptions\BadUpgradeException;
use Brash\Websocket\Http\RequestVerifier;
use Brash\Websocket\Http\Response;
use Brash\Websocket\Utilities\KeyDigest;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final readonly class ConnectionHandshake
{

    public function handshake(RequestInterface $request): ResponseInterface|WebSocketException
    {
        if (
            !$request->hasHeader('Sec-WebSocket-Key') ||
            !$request->hasHeader('Upgrade') ||
            strtolower($request->getHeaderLine('Upgrade')) !== 'websocket'
        ) {
            throw new BadUpgradeException('Invalid WebSocket upgrade request.');
        }

        $requestVerifier = new RequestVerifier();
        $keyDigest = new KeyDigest();

        $result = $requestVerifier->verify($request);
        if ($result !== null) {
            return $result;
        }
        $secWebsocketKeyHeaders = $request->getHeader('sec-websocket-key');

        if (empty($secWebsocketKeyHeaders)) {
            return new WebSocketException("No sec-websocket-key header found in request");
        }

        $secWebsocketKey = array_pop($secWebsocketKeyHeaders);

        $acceptKey = $keyDigest->createSocketAcceptKey($secWebsocketKey);

        return (new Response(101))
            ->withAddedHeader('Upgrade', 'websocket')
            ->withAddedHeader('Connection', 'Upgrade')
            ->withAddedHeader('Sec-WebSocket-Accept', $acceptKey);
    }
}
