<?php

namespace Kit\Websocket\Frame\DataManipulation\Functions;

final class GetInfoBytesLengthFunction
{
    /**
     * Get length of meta data of the frame.
     * Metadata contains type of frame, length, masking key and rsv data.
     */
    public static function getInfoBytesLen(int $lenSize, bool $isMasked): int
    {
        return intval((9 + $lenSize) / 8 + ($isMasked ? 4 : 0));
    }
}
