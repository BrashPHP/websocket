<?php

declare(strict_types=1);

namespace Brash\Websocket\Http;

use Brash\Websocket\Exceptions\WebSocketException;
use Psr\Http\Message\RequestInterface;

final class RequestVerifier
{
    const array SUPPORTED_VERSIONS = [13];
    /** https://tools.ietf.org/html/rfc6455#section-4.2 */
    public function verify(RequestInterface $request): ?WebSocketException
    {
        $exception = null;
        if ($request->getProtocolVersion() !== '1.1') {
            $exception = new WebsocketException(
                \sprintf('Wrong http version, HTTP/1.1 expected, "%s" received.', $request->getProtocolVersion())
            );
        }

        if (\strtoupper($request->getMethod()) !== 'GET') {
            $exception = new WebsocketException(
                \sprintf('Wrong http method, GET expected, "%" received.', $request->getMethod())
            );
        }

        $secWebSocketKey = $request->getHeaderLine('Sec-WebSocket-Key')[0] ?? "";
        if (empty($secWebSocketKey)) {
            $exception = new WebsocketException(
                \sprintf('Missing websocket key header.')
            );
        }

        $upgradeHeader = $request->getHeader('upgrade')[0] ?? '';

        if (empty($upgradeHeader) || 'websocket' !== \strtolower($upgradeHeader)) {
            $exception = new WebSocketException(
                \sprintf('Wrong or missing upgrade header.')
            );
        }

        $version = $request->getHeader('Sec-WebSocket-Version')[0] ?? '';
        if (!\in_array(intval($version), self::SUPPORTED_VERSIONS)) {
            $exception = new WebSocketException(sprintf('Version %s not supported by now.', $version));
        }

        return $exception;
    }
}
