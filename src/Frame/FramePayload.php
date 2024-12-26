<?php

declare(strict_types=1);

namespace Brash\Websocket\Frame;
use Brash\Websocket\Frame\DataManipulation\Functions\GetInfoBytesLengthFunction;

/**
 * Frame Payload operations are contained within this class to provide granular acess to data functions and operations.
 */
class FramePayload
{

    public function __construct(
        private readonly string $payload,
        private readonly int $payloadLen,
        private readonly int $lenSize,
        private readonly string $maskingKey
    ) {
    }

    /**
     * Set the masking key of the frame. As a consequence the frame is now considered as masked.
     *
     * @param string $mask
     * @return self
     */
    public function maskFrame(string $mask): self
    {
        $this->maskingKey = $mask;

        return $this;
    }

    /**
     * This method works for mask and unmask (it's the same operation)
     */
    public function applyMask(string $payload): string
    {
        $res = '';
        $mask = $this->maskingKey;

        for ($i = 0; $i < $this->payloadLen; $i++) {
            $payloadByte = $payload[$i];
            $res .= $payloadByte ^ $mask[$i % 4];
        }

        return $res;
    }

    public function getRawPayload(): string
    {
        return $this->payload;
    }

    public function getPayload(): string
    {
        if ($this->isMasked()) {
            return $this->applyMask($this->payload);
        }

        return $this->payload;
    }

    public function getPayloadLength(): int
    {
        return $this->payloadLen;
    }

    public function getMaskingKey()
    {
        return $this->maskingKey;
    }

    public function getLenSize(): int
    {
        return $this->lenSize;
    }

    /**
     * Calculate the first length based on payload length.
     */
    public function getFirstLength(): int
    {
        $payloadLen = $this->payloadLen;

        if ($payloadLen < 126) {
            return $payloadLen;
        }

        return $payloadLen < 65536 ? 126 : 127;
    }

    /**
     * Calculate the second length based on payload length and first length.
     */
    public function getSecondLength(): ?int
    {
        $payloadLen = $this->payloadLen;
        $firstLen = $this->getFirstLength();

        if ($firstLen === 126) {
            return $payloadLen;
        }

        return $firstLen === 127 ? $payloadLen : null;
    }

    public function isMasked(): bool
    {
        return $this->maskingKey !== '';
    }

    public function getTheoricDataLength(): int
    {
        $payloadLen = $this->payloadLen;

        $infoBytesLen = GetInfoBytesLengthFunction::getInfoBytesLen(
            lenSize: $this->lenSize,
            isMasked: $this->isMasked()
        );

        return $infoBytesLen + $payloadLen;
    }
}
