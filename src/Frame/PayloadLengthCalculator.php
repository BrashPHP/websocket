<?php

declare(strict_types=1);

namespace Kit\Websocket\Frame;

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
 */
final class PayloadLengthCalculator
{
    public function getPayloadLength(int $secondByte): PayloadLengthDto
    {
        // Receives information from second byte and remove mask bit from it using bitwise AND.
        $payloadLen = $secondByte & 127;
        $lenSize = 7;

        return match ($payloadLen) {
            126 => new PayloadLengthDto(
                size: $lenSize + 16,
                length: $payloadLen,
                threshold: 3,
                force8Bits: false
            ),
            127 => new PayloadLengthDto(
                size: $lenSize + 64,
                length: $payloadLen,
                threshold: 9,
                force8Bits: true
            ),
            default => new PayloadLengthDto(
                size: $lenSize,
                length: $payloadLen,
                threshold: 1,
                force8Bits: false
            )
        };
    }
}
