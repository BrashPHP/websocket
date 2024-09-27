<?php
/**
 * This file is a part of the Woketo package.
 *
 * (c) Nekland <dev@nekland.fr>
 *
 * For the full license, refer to the LICENSE file in the root directory of this project.
 */

declare(strict_types=1);

namespace Kit\Websocket\Frame;

use Kit\Websocket\DataManipulation\FrameByteManipulation;
use Kit\Websocket\Frame\FrameTypeEnum;
use Nekland\Woketo\Exception\Frame\ControlFrameException;
use Nekland\Woketo\Exception\Frame\IncompleteFrameException;
use Nekland\Woketo\Exception\Frame\InvalidFrameException;
use Nekland\Woketo\Exception\Frame\ProtocolErrorException;
use Nekland\Woketo\Exception\Frame\TooBigControlFrameException;
use Nekland\Woketo\Exception\Frame\TooBigFrameException;
use Nekland\Woketo\Utils\BitManipulation;
use function Kit\Websocket\functions\frameSize;

class Frame
{
    public const int MAX_CONTROL_FRAME_SIZE = 125;
    private int $maxPayloadSize;

    private string $rawData;
    private int $frameSize;
    private bool $final;
    private int $payloadLen;
    private int $payloadLenSize;
    private string $payload;
    private string $mask;
    /**
     * Defined the opcode used in the frame.
     * The opcode is the second part of the very first byte.
     * Thus it is possible to extract it via a simple `$fullbyte & 15` bitwise operation.
     */
    public FrameTypeEnum $opcode;
    private int $infoBytesLen;
    private array $config;

    /**
     * Creates a frame with defaults for a null data and maximum payload size of 0.5MiB
     * 
     * @param ?string $data The data received while reading the source stream.
     * @param int $maxPayloadSize (0.5MiB) Adjust if needed.
     */
    public function __construct(?string $data = null, int $maxPayloadSize = 524288)
    {
        /** @todo Check if this even makes any sense */
        if ($data !== null) {
            $this->setRawData($data);
        }

        $this->maxPayloadSize = $maxPayloadSize;
        $frameByteManipulation = new FrameByteManipulation();
        $firstByte = $frameByteManipulation->nthByte(frame: $data, byteNumber: 0);
        $secondByte = $frameByteManipulation->nthByte(frame: $data, byteNumber: 1);
        $this->processFirstByte($firstByte);
    }

    private function processFirstByte(int|string $frame)
    {

    }

    private function processSecondByte()
    {

    }

    public function setRawData(string $rawData): self
    {
        $this->rawData = $rawData;
        $this->frameSize = frameSize($rawData);

        if ($this->frameSize < 2) {
            throw new IncompleteFrameException('Frame data is too short to be valid.');
        }
        $this->parseFrameData();

        try {
            $this->validateFrameSize();
        } catch (TooBigFrameException $e) {
            $this->frameSize = $e->getMaxLength();
            $this->rawData = BitManipulation::truncateData($this->rawData, $this->frameSize);
        }

        $this->validateFrame($this);

        return $this;
    }

    public function getRawData(): string
    {
        if ($this->rawData !== '') {
            return $this->rawData;
        }

        if (!$this->isValid()) {
            throw new InvalidFrameException('Composed frame is invalid.');
        }

        $this->generateRawData();

        return $this->rawData;
    }

    public function isFinal(): bool
    {
        return $this->final;
    }

    public function setFinal(bool $final): self
    {
        $this->final = $final;
        return $this;
    }

    public function setOpcode(FrameTypeEnum $frameTypeEnum): self
    {
        $this->rawData = '';
        $this->opcode = $frameTypeEnum;

        return $this;
    }

    public function setMaskingKey(string $mask): self
    {
        $this->mask = $mask;
        $this->rawData = '';
        return $this;
    }

    public function getMaskingKey(): string
    {
        if ($this->mask !== null) {
            return $this->mask;
        }

        if (!$this->isMasked()) {
            return '';
        }

        return $this->mask = BitManipulation::extractMaskingKey($this->rawData, $this->payloadLenSize);
    }

    public function getPayload(): string
    {
        if ($this->payload !== null) {
            return $this->payload;
        }

        $payloadData = BitManipulation::extractPayload($this->rawData, $this->getInfoBytesLen(), $this->payloadLen);

        if ($this->isMasked()) {
            return $this->payload = $this->applyMask($payloadData);
        }

        return $this->payload = $payloadData;
    }

    public function setPayload(string $payload): self
    {
        $this->rawData = '';
        $this->payload = $payload;
        $this->payloadLen = frameSize($payload);
        $this->adjustPayloadLengthSize();

        return $this;
    }

    public function getPayloadLength(): int
    {
        if ($this->payloadLen !== null) {
            return $this->payloadLen;
        }

        $this->payloadLen = extractPayloadLength($this->rawData, $this->secondByte, $this->config['maxPayloadSize']);
        return $this->payloadLen;
    }

    public function isMasked(): bool
    {
        if ($this->mask !== null) {
            return true;
        }

        return BitManipulation::isMasked($this->secondByte);
    }

    private function applyMask(string $data): string
    {
        return BitManipulation::applyMask($data, $this->getMaskingKey());
    }

    private function parseFrameData(): void
    {
        $this->firstByte = BitManipulation::getNthByte($this->rawData, 0);
        $this->secondByte = BitManipulation::getNthByte($this->rawData, 1);
        $this->final = BitManipulation::isFinalFrame($this->firstByte);
        $this->payloadLen = $this->getPayloadLength();
    }

    private function validateFrameSize(): void
    {
        $infoBytesLen = $this->getInfoBytesLen();
        $theoreticalDataLength = $infoBytesLen + $this->payloadLen;

        if ($this->frameSize < $theoreticalDataLength) {
            throw new IncompleteFrameException('Frame data length mismatch.');
        }

        if ($this->frameSize > $theoreticalDataLength) {
            throw new TooBigFrameException($theoreticalDataLength);
        }

        if ($this->opcode === FrameTypeEnum::Close && $this->payloadLen === 1) {
            throw new ProtocolErrorException('Close frame cannot be 1 byte, 2 bytes are required.');
        }
    }

    public function validateFrame(Frame $frame): void
    {
        if ($frame->isControlFrame()) {
            if (!$frame->isFinal()) {
                throw new ControlFrameException('Control frames cannot be fragmented.');
            }

            if ($frame->getPayloadLength() > self::MAX_CONTROL_FRAME_SIZE) {
                throw new TooBigControlFrameException('Control frames cannot exceed 125 bytes.');
            }
        }
    }

    public function isValid(): bool
    {
        return $this->opcode !== null;
    }

    public function isControlFrame(): bool
    {
        return $this->getOpcode() >= 8;
    }

    public function setConfig(array $config = []): self
    {
        $this->config = array_merge([
            'maxPayloadSize' => self::MAX_PAYLOAD_SIZE,
        ], $config);

        return $this;
    }

    private function getInfoBytesLen(): int
    {
        if ($this->infoBytesLen !== null) {
            return $this->infoBytesLen;
        }

        return $this->infoBytesLen = BitManipulation::calculateInfoBytesLen($this->payloadLen);
    }

    private function generateRawData(): void
    {
        // Implementation for generating the raw data based on set fields.
        // This part would depend on how you want to serialize the frame data.
    }

    private function adjustPayloadLengthSize(): void
    {
        $this->payloadLenSize = BitManipulation::getPayloadLenSize($this->payloadLen);
    }
}
