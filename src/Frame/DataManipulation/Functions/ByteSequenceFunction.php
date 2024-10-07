<?php

declare(strict_types=1);

namespace Kit\Websocket\Frame\DataManipulation\Functions;

use Kit\Websocket\Frame\DataManipulation\Exceptions\{
    BadByteSizeRequestedException,
    InvalidRangeException,
    NotLongEnoughException,
    PhpByteLimitationException,
};
use Kit\Websocket\Frame\Exceptions\InvalidNegativeNumberFrameException;

final class ByteSequenceFunction 
{
        /**
     * Extracts and returns a byte sequence from a frame between the specified range.
     *
     * @param string|int $frame       The frame data, either as a string or an integer.
     * @param int        $from        Byte position to start (must be less than $to).
     * @param int        $to          Byte position to stop (inclusive).
     * @param bool       $force8bytes Whether to force 8-byte behavior, addressing PHP's unsigned int limitation.
     *
     * @throws NotLongEnoughException   if the string frame is shorter than expected.
     * @throws InvalidNegativeNumberFrameException    if the integer frame is negative.
     * @throws InvalidRangeException
     * @throws BadByteSizeRequestedException
     * @throws PhpByteLimitationException
     *
     * @return int The extracted byte sequence as an integer.
     */
    public static function bytesFromTo(string|int $frame, int $from, int $to, bool $force8bytes = false): int
    {
        // Validate byte range
        $byteRange = $to - $from + 1;

        if ($byteRange < 1) {
            throw new InvalidRangeException();
        }

        if ($byteRange > 7 && (!$force8bytes || $byteRange !== 8)) {
            if ($force8bytes) {
                throw new BadByteSizeRequestedException($byteRange);
            }
            throw new PhpByteLimitationException();
        }

        // Handle string input
        if (\is_string($frame)) {
            if (\strlen($frame) < $to + 1) {
                throw new NotLongEnoughException();
            }

            $subString = \substr($frame, $from, $byteRange);
            $result = 0;

            // Convert bytes to integer
            foreach (\str_split($subString) as $char) {
                $result = ($result << 8) + \ord($char);
            }

            return $result;
        }

        // Handle integer frame
        if ($frame < 0) {
            throw new InvalidNegativeNumberFrameException();
        }

        $result = 0;

        // Extract bytes from integer
        for ($i = $from; $i <= $to; $i++) {
            $result = ($result << 8) + GetNthByteFunction::nthByte($frame, $i);
        }

        return $result;
    }
}

