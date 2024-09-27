<?php

namespace Kit\Websocket\DataManipulation;

use Kit\Websocket\DataManipulation\Exceptions\NotLongEnoughException;
use function Kit\Websocket\functions\frameSize;

/**
 * Class BitManipulation
 *
 * Glossary:
 *   - in this context, a "frame" is an assembly of bytes represented by a "byte-string" or a (signed) int.
 */
final class FrameByteManipulation
{
    /**
     * Get a specific byte inside a frame represented by an int or a string. 
     * Offset starts at 0,
     */
    public function nthByte(int|string $frame, int $byteNumber): int
    {
        if (\is_string($frame)) {
            $len = frameSize($frame);

            if ($byteNumber < 0 || $byteNumber > ($len - 1)) {
                throw new \InvalidArgumentException(
                    \sprintf('The frame is only %s bytes large but you tried to get the %sth byte.', $len, $byteNumber)
                );
            }

            return \ord($frame[$byteNumber]);
        }


        if ($frame < 0) {
            throw new \InvalidArgumentException(
                \sprintf('This method does not support negative ints as parameter for now. %s given.', $byteNumber)
            );
        }
        $hex = \dechex($frame);
        $len = frameSize($hex);

        // Index of the first octal of the wanted byte
        $realByteNth = $byteNumber * 2;

        if ($byteNumber < 0 || ($realByteNth + 1) > $len) {
            throw new \InvalidArgumentException(
                \sprintf('Impossible to get the byte %s from the frame %s.', $byteNumber, $frame)
            );
        }

        // Considering FF12AB (number) if you want the byte represented by AB you need to get the
        // first letter, shift it of 4 and add the next letter.
        // This may seems weird but that's because you read numbers from right to left.

        // _Notice that if the number is from right to left, your data is still from left to right_
        return intval((\hexdec($hex[$realByteNth]) << 4) + \hexdec($hex[$realByteNth + 1]));
    }

    /**
     * @param string|int $frame
     * @param int        $from        Byte where to start (should be inferior to $to).
     * @param int        $to          Byte where to stop (considering it starts at 0). The `to` value include the target
     *                                byte.
     * @param bool       $force8bytes By default PHP have a wrong behavior with 8 bytes variables. If you have 8 bytes
     *                                the returned int will be negative (because unsigned integers does not exists in PHP)
     */
    public function bytesFromTo(string|int $frame, int $from, int $to, bool $force8bytes = false): int
    {
        // No more than 64b (which return negative number when the first bit is specified)
        if (($to - $from) > 7 && (!$force8bytes && ($to - $from) !== 8)) {
            if ($force8bytes) {
                throw new \InvalidArgumentException(sprintf('Not more than 8 bytes (64bit) is supported by this method and you asked for %s bytes.', $to - $from));
            }
            throw new \InvalidArgumentException('PHP limitation: getting more than 7 bytes will return a negative number because unsigned int does not exist.');
        }

        if (\is_string($frame)) {
            if ((frameSize($frame) - 1) < $to) {
                throw new NotLongEnoughException('The frame is not long enough.');
            }

            $subStringLength = $to - $from + 1;
            // Getting responsible bytes
            $subString = \substr($frame, $from, $subStringLength);
            $res = 0;

            // for each byte, getting ord
            for ($i = 0; $i < $subStringLength; $i++) {
                $res <<= 8;
                $res += \ord($subString[$i]);
            }

            return $res;
        }

        if ($frame < 0) {
            throw new \InvalidArgumentException('The frame cannot be a negative number');
        }

        $res = 0;

        for ($i = $from; $i <= $to; $i++) {
            $res <<= 8;
            $res += $this->nthByte($frame, $i);
        }

        return $res;
    }
}
