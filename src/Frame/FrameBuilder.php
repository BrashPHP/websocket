<?php

declare(strict_types=1);

namespace Kit\Websocket\Frame;

use Kit\Websocket\Frame\DataManipulation\Functions\ByteSequenceFunction;
use Kit\Websocket\Frame\DataManipulation\Functions\BytesFromToStringFunction;
use Kit\Websocket\Frame\DataManipulation\Functions\GetInfoBytesLengthFunction;
use Kit\Websocket\Frame\DataManipulation\Functions\GetNthByteFunction;
use Kit\Websocket\Frame\Enums\FrameTypeEnum;
use Kit\Websocket\Frame\Enums\InspectionFrameEnum;
use function Kit\Websocket\functions\frameSize;
use function Kit\Websocket\functions\intToBinaryString;
use function Kit\Websocket\functions\nthBitFromByte;


final class FrameBuilder
{
    public function build(string $data, PayloadLengthDto $lengthInformation): Frame|\Exception
    {
        $firstByte = GetNthByteFunction::nthByte(frame: $data, byteNumber: 0);
        $frameMetadata = $this->processMetadata($firstByte);
        $opcode = $this->processOpcode($firstByte);

        $framePayload = $this->processPayload(
            rawData: $data,
            payloadLengthDto: $lengthInformation
        );

        return new Frame(
            $opcode,
            $frameMetadata,
            $framePayload
        );
    }

    public function createFromPayload(string $payload, FrameTypeEnum $frameTypeEnum, bool $createMask): Frame
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

        return new Frame(
            $frameTypeEnum,
            $metadata,
            $framePayload
        );
    }

    /**
     * The first byte holds metadata about the frame. So it needs to be processed in order to create a metadata object from the Frame.
     */
    public function processMetadata(int $firstByte): FrameMetadata
    {
        return new FrameMetadata(firstByte: $firstByte);
    }

    /**
     * The opcode is defined by the second half of the first byte.
     */
    public function processOpcode(int $firstByte): FrameTypeEnum
    {
        $opcode = FrameTypeEnum::tryFrom(value: $firstByte & 15);
        if (!is_null($opcode)) {
            return $opcode;
        }

        throw new \InvalidArgumentException("Invalid Opcode for first byte {$firstByte}");
    }

    /**
     * Generates a payload from the raw frame.
     *
     * @throws \Kit\Websocket\Frame\DataManipulation\Exceptions\NotLongEnoughException if the string frame is shorter than expected.
     * @throws \Kit\Websocket\Frame\Exceptions\InvalidNegativeNumberFrameException if the integer frame is negative.
     * @throws \Kit\Websocket\Frame\DataManipulation\Exceptions\InvalidRangeException
     * @throws \Kit\Websocket\Frame\DataManipulation\Exceptions\BadByteSizeRequestedException
     * @throws \Kit\Websocket\Frame\DataManipulation\Exceptions\PhpByteLimitationException
     */
    public function processPayload(string $rawData, PayloadLengthDto $payloadLengthDto): FramePayload
    {
        $lenSize = $payloadLengthDto->size;
        $payloadLen = $payloadLengthDto->getRealLength($rawData);

        $isMasked = $this->getIsMasked(rawData: $rawData);
        $payload = $this->extractPayload(
            $rawData,
            $isMasked,
            $lenSize,
            $payloadLen
        );

        $maskingKey = $isMasked ? $this->extractMaskingKey($rawData, $lenSize) : "";

        return new FramePayload(
            $payload,
            $payloadLen,
            $lenSize,
            $maskingKey
        );
    }

    /**
     * The very first bit of the second byte explicits whether a mask is being used.
     */
    private function getIsMasked(string $rawData): bool
    {
        $secondByte = GetNthByteFunction::nthByte(frame: $rawData, byteNumber: 1);

        return nthBitFromByte($secondByte, 1) === 1;
    }

    private function extractPayload(string $rawData, bool $isMasked, int $lenSize, int $payloadLen): string
    {
        $infoBytesLen = GetInfoBytesLengthFunction::getInfoBytesLen($lenSize, $isMasked);

        return (string) BytesFromToStringFunction::getBytesFromToString(
            $rawData,
            $infoBytesLen,
            $payloadLen,
            inspectionFrameEnum: InspectionFrameEnum::MODE_PHP
        );
    }

    private function extractMaskingKey(string $rawData, int $lenSize)
    {
        // 8 is the numbers of bits before the payload len.
        $start = (9 + $lenSize) / 8;

        $value = ByteSequenceFunction::bytesFromTo(frame: $rawData, from: $start, to: $start + 3);

        return intToBinaryString($value, 4);
    }
}

