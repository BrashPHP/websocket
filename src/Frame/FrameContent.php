<?php
namespace Kit\Websocket\Frame;
use Kit\Websocket\DataManipulation\Exceptions\NotLongEnoughException;
use Kit\Websocket\Frame\Exceptions\TooBigFrameException;

class FrameContent
{
    public readonly int $payloadLen;

    public function __construct(
        private string $rawData,
        private int $secondByte,
        private int $maxPayloadSize
    ) {
    }

    public function getPayloadLength(): int
    {
        if (!is_null($this->payloadLen)) {
            return $this->payloadLen;
        }

        // Get the first part of the payload length by removing mask information from the second byte
        $payloadLen = $this->secondByte & 127;
        $payloadLenSize = 7;

        try {
            if ($payloadLen === 126) {
                $payloadLenSize += 16;
                $payloadLen = bytesFromTo($this->rawData, 2, 3);
            } else if ($payloadLen === 127) {
                $payloadLenSize += 64;

                $payloadLen = bytesFromTo($this->rawData, 2, 9, true);
            }

            // Check < 0 because 64th bit is the negative one in PHP.
            if ($payloadLen < 0 || $payloadLen > $this->maxPayloadSize) {
                throw new TooBigFrameException($this->maxPayloadSize);
            }

            return $this->payloadLen = $payloadLen;
        } catch (NotLongEnoughException $e) {
            throw new IncompleteFrameException('Impossible to determine the length of the frame because message is too small.');
        }
    }
}
