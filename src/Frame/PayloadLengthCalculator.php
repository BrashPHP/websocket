<?php

namespace Kit\Websocket\Frame;

use Kit\Websocket\Frame\DataManipulation\Exceptions\{
    InvalidRangeException,
    BadByteSizeRequestedException,
    PhpByteLimitationException
};
use Kit\Websocket\Frame\DataManipulation\Functions\ByteSequenceFunction;
use Kit\Websocket\Frame\Exceptions\{
    InvalidNegativeNumberFrameException,
    IncompleteFrameException
};
use Kit\Websocket\Frame\DataManipulation\Functions\GetNthByteFunction;


class PayloadLengthCalculator
{
    /**
     * 
     * Note: 
     * If the size integer is lesser than 126, that is the content size.
     * If the size integer is exactly 126, then the message payload is too long to be encoded in just 7 bits. 
     * So, the protocol tells us that the next 2 bytes (16 bits) are the actual payload length,
     * which will need to be converted into a 16-bit unsigned integer (since we're not dealing with 8 bits anymore).
     * Else, if the size is exactly 127, then the message is even larger, 
     * and we'll allocate the next 8 bytes (64 bits) as 
     * the payload length which will need to be converted into a 64-bit unsigned integer.
     * 
     * Detail: Whether to force 8-byte behavior, 
     * addressing PHP's unsigned int limitation, 
     * will occur if payload length
     * is of value 127, since retrieving more than 7 bytes will result in a negative value due to lack of unsigned integers.
     *
     * @return array{int, int} length size and payload length
     *
     * @throws \InvalidArgumentException
     * @throws InvalidRangeException
     * @throws BadByteSizeRequestedException
     * @throws PhpByteLimitationException
     * @throws InvalidNegativeNumberFrameException
     */
    public function getLength(string $rawData): array
    {
        /**
         * Receives information from second byte and remove mask bit from it using bitwise AND.
         */
        $secondByte = GetNthByteFunction::nthByte(frame: $rawData, byteNumber: 1);
        $payloadLen = $secondByte & 127;
        $lenSize = 7;

        if ($payloadLen < 126) {
            return [$lenSize, $payloadLen];
        }

        $defaultStartByte = 2;
        $to = 1;
        $force8Bits = false;

        if ($payloadLen === 126) {
            $lenSize += 16;
            $to = 3;
        }

        if ($payloadLen === 127) {
            $lenSize += 64;
            $to = 9;
            $force8Bits = true;
        }

        if (\strlen($rawData) < $to + 1) {
            throw new IncompleteFrameException('Impossible to determine the length of the frame because message is too small.');
        }

        return [
            $lenSize,
            ByteSequenceFunction::bytesFromTo(
                frame: $rawData,
                from: $defaultStartByte,
                to: $to,
                force8bytes: $force8Bits
            )
        ];
    }
}

