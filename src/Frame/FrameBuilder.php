<?php

declare(strict_types=1);

namespace Kit\Websocket\Frame;

use Kit\Websocket\Frame\DataManipulation\Functions\ByteSequenceFunction;
use Kit\Websocket\Frame\DataManipulation\Functions\BytesFromToStringFunction;
use Kit\Websocket\Frame\DataManipulation\Functions\GetNthByteFunction;
use Kit\Websocket\Frame\Enums\FrameTypeEnum;
use Kit\Websocket\Frame\Enums\InspectionFrameEnum;
use Kit\Websocket\Frame\Exceptions\IncompleteFrameException;
use Kit\Websocket\Frame\FrameValidation\ValidationUponOpCode;
use function Kit\Websocket\functions\frameSize;
use function Kit\Websocket\functions\intToBinaryString;
use function Kit\Websocket\functions\nthBitFromByte;
use function Kit\Websocket\functions\println;

final class FrameBuilder
{
    public function build(string $data, int $maxPayloadSize = 524288): Frame
    {
        $firstByte = GetNthByteFunction::nthByte(frame: $data, byteNumber: 0);
        $frameMetadata = $this->processMetadata($firstByte);
        $opcode = $this->processOpcode($firstByte);
        $framePayload = $this->processPayload($data);

        $maybeTruncated = $this->checkSize(
            $data,
            $framePayload
        );

        if ($data !== $maybeTruncated) {
            return $this->build($maybeTruncated);
        }

        $frame = new Frame(
            $opcode,
            $frameMetadata,
            $framePayload,
            $maxPayloadSize
        );

        $this->validateFrame($frame);

        return $frame;
    }

    public static function createFromPayload(string $payload, FrameTypeEnum $frameTypeEnum, bool $createMask): Frame
    {
        $payloadLen = frameSize($payload);
        $payloadLenSize = 7;

        if ($payloadLen > 126 && $payloadLen < 65536) {
            $payloadLenSize += 16;
        } elseif ($payloadLen > 126) {
            $payloadLenSize += 64;
        }

        $mask = $createMask ? random_bytes(4) : "";
        $framePayload = new FramePayload($payload, $payloadLen, $payloadLenSize, $mask);

        $metadata = new FrameMetadata(firstByte: 128);

        return new Frame($frameTypeEnum, $metadata, $framePayload);
    }

    public function validateFrame(Frame $frame)
    {
        (new ValidationUponOpCode())->validate($frame);
    }

    /**
     * The first byte holds metadata about the frame. So it needs to be processed in order to create a metadata object from the Frame.
     */
    private function processMetadata(int $firstByte): FrameMetadata
    {
        return new FrameMetadata(firstByte: $firstByte);
    }

    /**
     * The opcode is defined by the second half of the first byte.
     */
    private function processOpcode(int $firstByte): FrameTypeEnum
    {
        return FrameTypeEnum::from(value: $firstByte & 15);
    }

    /**
     * Generates a payload from the frame.
     */
    private function processPayload(string $rawData): FramePayload
    {
        [$lenSize, $payloadLen] = $this->getPayloadLength($rawData);
        $isMasked = $this->getIsMasked($rawData);
        $payload = $this->extractPayload($rawData, $isMasked);
        $maskingKey = $isMasked ? $this->extractMaskingKey($rawData, $lenSize) : "";

        return new FramePayload(
            $payload,
            $payloadLen,
            $lenSize,
            $maskingKey
        );
    }

    private function extractMaskingKey(string $rawData, int $lenSize)
    {
        // 8 is the numbers of bits before the payload len.
        $start = (9 + $lenSize) / 8;

        $value = ByteSequenceFunction::bytesFromTo(frame: $rawData, from: $start, to: $start + 3);

        return intToBinaryString($value, 4);
    }

    private function getPayloadLength(string $rawData): array
    {
        $payloadLengthCalculator = new PayloadLengthCalculator();

        return $payloadLengthCalculator->getLength($rawData);
    }

    /**
     * The very first bit of the second byte explicits whether a mask is being used.
     */
    private function getIsMasked(string $rawData): bool
    {
        $secondByte = GetNthByteFunction::nthByte(frame: $rawData, byteNumber: 1);

        return nthBitFromByte($secondByte, 1) === 1;
    }

    private function extractPayload(string $rawData, bool $isMasked): string
    {
        [$lenSize, $payloadLen] = $this->getPayloadLength($rawData);
        $infoBytesLen = $this->getInfoBytesLen($lenSize, $isMasked);

        return (string) BytesFromToStringFunction::getBytesFromToString(
            $rawData,
            $infoBytesLen,
            $payloadLen,
            inspectionFrameEnum: InspectionFrameEnum::MODE_PHP
        );
    }

    /**
     * Get length of meta data of the frame.
     * Metadata contains type of frame, length, masking key and rsv data.
     */
    public function getInfoBytesLen(int $lenSize, bool $isMasked): int
    {
        return intval((9 + $lenSize) / 8 + ($isMasked ? 4 : 0));
    }

    /**
     * This generates a string of 4 random bytes. (WebSocket mask according to the RFC)
     *
     * @return string
     */
    public function generateMask(): string
    {
        return random_bytes(4);
    }

    /**
     * Checks payload size and truncate data if needed.
     * @param string $data
     * @throws \Kit\Websocket\Frame\Exceptions\IncompleteFrameException
     * @throws \Kit\Websocket\Frame\Exceptions\ProtocolErrorException
     *
     * @return string
     */
    public function checkSize(string $data, FramePayload $framePayload): string
    {
        $payloadLen = $framePayload->getPayloadLength();
        $lenSize = $framePayload->getLenSize();
        $isMasked = $framePayload->isMasked();
        $infoBytesLen = $this->getInfoBytesLen(lenSize: $lenSize, isMasked: $isMasked);
        $frameSize = frameSize($data);
        $theoricDataLength = $infoBytesLen + $payloadLen;

        if ($frameSize < $theoricDataLength) {
            throw new IncompleteFrameException(
                message:
                sprintf(
                    'Impossible to retrieve %s bytes of payload when the full frame is %s bytes long.',
                    $theoricDataLength,
                    $frameSize
                )
            );
        }

        if ($frameSize > $theoricDataLength) {
            return BytesFromToStringFunction::getBytesFromToString(
                frame: $data,
                from: 0,
                to: $theoricDataLength,
                inspectionFrameEnum: InspectionFrameEnum::MODE_PHP
            );
        }

        return $data;
    }
}

