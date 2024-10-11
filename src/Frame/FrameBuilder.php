<?php

declare(strict_types=1);

namespace Kit\Websocket\Frame;

use Kit\Websocket\Frame\DataManipulation\Functions\BytesFromToStringFunction;
use Kit\Websocket\Frame\DataManipulation\Functions\GetInfoBytesLengthFunction;
use Kit\Websocket\Frame\DataManipulation\Functions\GetNthByteFunction;
use Kit\Websocket\Frame\Enums\FrameTypeEnum;
use Kit\Websocket\Frame\Enums\InspectionFrameEnum;
use Kit\Websocket\Frame\Exceptions\IncompleteFrameException;
use function Kit\Websocket\functions\frameSize;


final class FrameBuilder
{
    private PayloadExtractor $payloadExtractor;
    private PayloadLengthCalculator $payloadLengthCalculator;

    public function __construct(private int $maxPayloadSize)
    {
        $this->payloadExtractor = new PayloadExtractor();
        $this->payloadLengthCalculator = new PayloadLengthCalculator();
    }

    /**
     * Creates a new frame based on incoming streaming data
     * @param string $data
     *
     * @throws \Kit\Websocket\Frame\DataManipulation\Exceptions\NotLongEnoughException if the string frame is shorter than expected.
     * @throws \Kit\Websocket\Frame\Exceptions\InvalidNegativeNumberFrameException if the integer frame is negative.
     * @throws \Kit\Websocket\Frame\DataManipulation\Exceptions\InvalidRangeException
     * @throws \Kit\Websocket\Frame\DataManipulation\Exceptions\BadByteSizeRequestedException
     * @throws \Kit\Websocket\Frame\DataManipulation\Exceptions\PhpByteLimitationException
     */
    public function build(string $data): Frame|IncompleteFrameException
    {
        $maxPayloadSize = $this->maxPayloadSize;
        $firstByte = GetNthByteFunction::nthByte(frame: $data, byteNumber: 0);
        $frameMetadata = $this->processMetadata($firstByte);
        $opcode = $this->processOpcode($firstByte);

        $payloadLengthResult = $this->getPayloadLength($data);
        if (!is_null($payloadLengthResult)) {
            $framePayload = $this->payloadExtractor->processPayload($data, $payloadLengthResult);

            $theoricDataLength = $this->getTheoricDataLength(framePayload: $framePayload);
            $frameSize = frameSize($data);
            
            if ($frameSize < $theoricDataLength) {
                return new IncompleteFrameException(
                    message:
                    sprintf(
                        'Impossible to retrieve %s bytes of payload when the full frame is %s bytes long.',
                        $theoricDataLength,
                        $frameSize
                    )
                );
            }

            return ($frameSize > $theoricDataLength) ?
                $this->build(
                    $this->truncateRawData(
                        $data,
                        $theoricDataLength
                    )
                ) : new Frame(
                    $opcode,
                    $frameMetadata,
                    $framePayload,
                    $maxPayloadSize
                );
        }

        return new IncompleteFrameException('Impossible to determine the length of the frame because message is too small.');
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

        return new Frame($frameTypeEnum, $metadata, $framePayload, $this->maxPayloadSize);
    }

    private function truncateRawData(string $rawData, int $theoricDataLength): string
    {
        return BytesFromToStringFunction::getBytesFromToString(
            frame: $rawData,
            from: 0,
            to: $theoricDataLength,
            inspectionFrameEnum: InspectionFrameEnum::MODE_PHP
        );
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
        $opcode = FrameTypeEnum::tryFrom(value: $firstByte & 15);
        if (!is_null($opcode)) {
            return $opcode;
        }

        throw new \InvalidArgumentException('Invalid Opcode');
    }

    public function getPayloadLength(string $rawData): ?PayloadLengthDto
    {
        $secondByte = GetNthByteFunction::nthByte(frame: $rawData, byteNumber: 1);
        $result = $this->payloadLengthCalculator->getPayloadLength($secondByte);

        if (\strlen($rawData) < $result->threshold + 1) {
            return null;
        }

        return $result;
    }

    /**
     * Checks payload size and truncate data if needed.
     *
     * @param string $data
     */
    public function getTheoricDataLength(FramePayload $framePayload): int
    {
        $payloadLen = $framePayload->getPayloadLength();
        $infoBytesLen = GetInfoBytesLengthFunction::getInfoBytesLen(
            lenSize: $framePayload->getLenSize(),
            isMasked: $framePayload->isMasked()
        );

        return $infoBytesLen + $payloadLen;
    }
}

