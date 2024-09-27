<?php

namespace Kit\Websocket;

use function Kit\Websocket\functions\getBytes;

final class Handshake
{
    public function handshaking(string $message): bool
    {
        $this->logger->debug(sprintf("=====Handshaking from client=====" . PHP_EOL));
        if (preg_match("/^GET/", $message)) {
            $matches = [];
            $pattern = "/Sec-WebSocket-Key: (.*)/i";
            $eol = "\r\n";
            if (preg_match($pattern, $message, $matches)) {

                [_, $secWebsocketHeaderValue] = $matches;
                $secWebsocketHeaderValue = trim($secWebsocketHeaderValue);
                $inputString = "{$secWebsocketHeaderValue}258EAFA5-E914-47DA-95CA-C5AB0DC85B11";

                // Calculate SHA-1 hash
                $sha1Hash = sha1($inputString, true); // `true` for raw output

                // Encode to Base64
                $base64Encoded = base64_encode($sha1Hash);

                // Convert the result to UTF-8 bytes (not necessary in PHP as strings are byte sequences)
                $utf8Bytes = mb_convert_encoding($base64Encoded, 'UTF-8');
                $parts = [
                    "HTTP/1.1 101 Switching Protocols",
                    "Connection: Upgrade",
                    "Upgrade: websocket",
                    "Sec-WebSocket-Accept: {$utf8Bytes}"
                ];
                $response = getBytes(implode($eol, $parts) . "\r\n\r\n");
            }
        }
    }
}
