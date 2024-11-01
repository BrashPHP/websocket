<?php

namespace Kit\Websocket\Utilities;

final class HandshakeResponder
{
    public function prepareHandshakeResponse(string $acceptKey): string
    {
        $lineSeparator = '\r\n';

        return implode(
            array_map(callback: fn($line): string => sprintf("%s%s", $line, $lineSeparator), array: [
                'HTTP/1.1 101 Switching Protocols',
                'Upgrade: websocket',
                'Connection: Upgrade',
                "sec-webSocket-accept: {$acceptKey}",
                // This empty line MUST be present for the response to be valid
                ''
            ], )
        );
    }
}
