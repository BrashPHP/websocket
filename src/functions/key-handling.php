<?php

declare(strict_types=1);

namespace Brash\Websocket\functions;

// defined in https://tools.ietf.org/html/rfc6455#section-4
const GUID = '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';

if (!function_exists('generateKey')) {
    /**
     * @param positive-int $length Random bytes to use to generate the key.
     *
     * @throws \Exception If generating a random key fails.
     */
    function generateKey(int $length = 16): string
    {
        return \base64_encode(\random_bytes($length));
    }
}

if (!function_exists('generateAcceptFromKey')) {

    /**
     * Generates the value for the Sec-WebSocket-Accept header based on the given Sec-Websocket-Key header value.
     */
    function generateAcceptFromKey(string $key): string
    {
        return \base64_encode(\sha1($key . GUID, true));
    }
}

if (!function_exists('validateAcceptForKey')) {
    /**
     * Determines if the Sec-WebSocket-Accept value given matches the expected value for the Sec-Websocket-Key header.
     */
    function validateAcceptForKey(string $accept, string $key): bool
    {
        return $accept === generateAcceptFromKey($key);
    }
}
