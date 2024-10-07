<?php

declare(strict_types=1);

namespace Kit\Websocket\Frame\DataManipulation\Functions;

use function Kit\Websocket\functions\frameSize;

final class GetNthByteFunction
{
        /**
     * Get a specific byte from a frame represented as either an integer or a string.
     * The byte offset starts at 0.
     *
     * @param positive-int|string $frame      The frame to extract the byte from.
     * @param int        $byteNumber The byte position to extract (starts at 0).
     *
     * @throws \InvalidArgumentException If the byte number is out of bounds or the input is invalid.
     *
     * @return int The extracted byte as an integer.
     */
    public static function nthByte(int|string $frame, int $byteNumber): int
    {
        // Handle string frame
        if (\is_string($frame)) {
            $len = frameSize($frame);

            if ($byteNumber >= $len) {
                throw new \InvalidArgumentException(
                    \sprintf('The frame is only %d bytes long, but byte %d was requested.', $len, $byteNumber)
                );
            }

            return \ord($frame[$byteNumber]);
        }

        // Handle integer frame
        if ($frame < 0) {
            throw new \InvalidArgumentException(
                \sprintf('Negative integers are not supported for frame values. Frame provided: %d.', $frame)
            );
        }

        $hex = \dechex($frame);
        $len = strlen($hex);

        // Calculate the index in the hex string
        $realByteIndex = $byteNumber * 2;

        if ($realByteIndex + 1 >= $len) {
            throw new \InvalidArgumentException(
                \sprintf('Requested byte %d is out of bounds for the given frame.', $byteNumber)
            );
        }

        // Extract the byte from the hex string, handle it as two hex characters
        return intval((\hexdec($hex[$realByteIndex]) << 4) + \hexdec($hex[$realByteIndex + 1]));
    }
}

