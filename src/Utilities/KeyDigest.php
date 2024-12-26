<?php

declare(strict_types=1);

namespace Brash\Websocket\Utilities;

use function sha1;
use function base64_encode;

final class KeyDigest
{
    const string WEBSOCKET_MAGIC_STRING_KEY = '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';

    public function createSocketAcceptKey(string $id): string
    {
        $hash = sha1($id . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true);
        
        return base64_encode($hash);
    }
}

