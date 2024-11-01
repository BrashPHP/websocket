<?php

namespace Kit\Websocket\Http;
use Kit\Websocket\Http\Exceptions\BadUpgradeException;
use Kit\Websocket\Http\Exceptions\NoHttpException;

class RequestFactory
{
    public static function createRequest(string $requestString): Request
    {
        $request = new Request();

        $lines = \explode("\r\n", $requestString);
        $firstLine = \array_shift($lines);

        $httpElements = \explode(' ', $firstLine);

        if (\count($httpElements) < 3) {
            throw new NoHttpException($firstLine);
        }

        $httpElements[2] = \trim($httpElements[2]);
        if (!\preg_match('/HTTP\/.+/', $httpElements[2])) {
            throw new NoHttpException($firstLine);
        }

        $request = $request->withProtocolVersion($httpElements[2]);

        $request = $request->withMethod($httpElements[0])->withUri(new Uri($httpElements[1]));

        if (
            empty($request->hasHeader('Sec-WebSocket-Key')) ||
            empty($request->hasHeader('Upgrade')) ||
            \strtolower($request->getHeader('Upgrade')) !== 'websocket'
        ) {
            throw new BadUpgradeException($requestString);
        }

        return $request;
    }
}
