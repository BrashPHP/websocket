<?php

namespace Brash\Websocket\Http;
use Brash\Websocket\Http\Exceptions\BadUpgradeException;
use Brash\Websocket\Http\Exceptions\NoHttpException;
use Psr\Http\Message\RequestInterface;

class RequestFactory
{
    public static function createRequest(string $requestString): RequestInterface
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
        $headerRegex = '/([\w-]+):\s(.*)/';
        $rawHeaders = array_filter(
            $lines,
            fn($line) => !empty ($line) && preg_match($headerRegex, $line) === 1
        );

        foreach ($rawHeaders as $rawHeader) {
            [$header, $value] = explode(":", $rawHeader);
            $request = $request->withAddedHeader($header, trim($value));
        }

        $httpVersionRegex = '/\d.\d/';
        preg_match($httpVersionRegex, $httpElements[2], $matches);

        $protocolVersion = array_pop($matches);

        $request = $request->withProtocolVersion($protocolVersion);

        $request = $request->withMethod($httpElements[0])->withUri(new Uri($httpElements[1]));

        return $request;
    }
}
