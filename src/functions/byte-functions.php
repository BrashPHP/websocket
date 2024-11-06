<?php

namespace Kit\Websocket\functions;

use Kit\Websocket\Frame\DataManipulation\Exceptions\InvalidBitException;
use Kit\Websocket\Frame\DataManipulation\Exceptions\InvalidByteException;

if (!function_exists('validateByte')) {
    function validateByte(int $byte)
    {
        if ($byte < 0 || $byte > 255) {
            throw new InvalidByteException($byte);
        }
    }
}
if (!function_exists('validateBit')) {
    function validateBit(int $bitNumber)
    {
        if ($bitNumber < 1 || $bitNumber > 8) {
            throw new InvalidBitException($bitNumber);
        }
    }
}

if (!function_exists('nthBitFromByte')) {
    function nthBitFromByte(int $byte, int $bitNumber): int
    {
        $realNth = 2 ** (8 - $bitNumber);

        return (int) ($realNth === ($byte & $realNth));
    }
}
