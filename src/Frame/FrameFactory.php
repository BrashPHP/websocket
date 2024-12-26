<?php

declare(strict_types=1);

namespace Brash\Websocket\Frame;

use Brash\Websocket\Frame\DataManipulation\Functions\BytesFromToStringFunction;
use Brash\Websocket\Frame\DataManipulation\Functions\GetNthByteFunction;
use Brash\Websocket\Frame\Enums\CloseFrameEnum;
use Brash\Websocket\Frame\Enums\FrameTypeEnum;
use Brash\Websocket\Frame\Enums\InspectionFrameEnum;
use Brash\Websocket\Frame\Exceptions\IncompleteFrameException;
use Brash\Websocket\Frame\FrameBuilder;

use Brash\Websocket\Message\Exceptions\LimitationException;
use function Brash\Websocket\functions\frameSize;
use function Brash\Websocket\functions\intToBinaryString;

/**
 * Class FrameFactory
 *
 * This class generates Frame objects for control frames.
 * https://tools.ietf.org/html/rfc6455#section-5.5
 *
 * Notice: a control frame cannot be larger than 125 bytes.
 */
class FrameFactory
{
    private readonly FrameBuilder $frameBuilder;
    private readonly PayloadLengthCalculator $payloadLengthCalculator;

    /**
     * (0.5MiB) Adjust if needed.
     */
    public const int MAX_PAYLOAD_SIZE = 524288;


    public function __construct(private readonly int $maxPayloadSize = 524288)
    {
        $this->frameBuilder = new FrameBuilder();
        $this->payloadLengthCalculator = new PayloadLengthCalculator();
    }

    /**
     * Returns a new frame if everything succeeds, otherwise throws an exception
     */
    public function newFrameFromRawData(string $rawData): Frame|\Exception
    {
        $payloadLengthDto = $this->getPayloadLengthFromRawData($rawData);

        if (\strlen($rawData) < $payloadLengthDto->threshold + 1) {
            return new IncompleteFrameException(
                'Impossible to determine the length of the frame because message is too small.'
            );
        }

        $frame = $this->frameBuilder->build($rawData, $payloadLengthDto);
        $framePayload = $frame->getFramePayload();
        $payloadLen = $framePayload->getPayloadLength();

        if ($payloadLen < 0 || $payloadLen > $this->maxPayloadSize) {
            return new LimitationException('The frame is too big to be processed.');
        }

        $frameSize = frameSize($rawData);

        $theoricDataLength = $framePayload->getTheoricDataLength();

        return match ($frameSize <=> $theoricDataLength) {
            0 => $frame,
            1 => $this->newFrameFromRawData(
                $this->truncateRawData(
                    $rawData,
                    $theoricDataLength
                )
            ),
            -1 => new IncompleteFrameException(
                sprintf(
                    'Impossible to retrieve %s bytes of payload when the full frame is %s bytes long.',
                    $theoricDataLength,
                    $frameSize
                )
            ),
        };
    }

    public function getPayloadLengthFromRawData(string $rawData): PayloadLengthDto{
        $secondByte = GetNthByteFunction::nthByte(frame: $rawData, byteNumber: 1);

        return $this->payloadLengthCalculator->getPayloadLength($secondByte);
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

    public function newFrame(string $payload, FrameTypeEnum $frameTypeEnum, bool $writeMask): Frame
    {
        return $this->frameBuilder->createFromPayload(
            $payload,
            $frameTypeEnum,
            $writeMask
        );
    }

    /**
     * @param CloseFrameEnum    $status One of the close constant code closing enum.
     * @param string $reason A little message that explain why closing.
     * @return Frame
     */
    public function createCloseFrame(CloseFrameEnum $status = CloseFrameEnum::CLOSE_NORMAL, string $reason = null): Frame
    {
        $content = intToBinaryString($status->value);
        if (!is_null($reason)) {
            $content .= $reason;
        }

        return $this->newFrame(
            payload: $content,
            frameTypeEnum: FrameTypeEnum::Close,
            writeMask: false
        );
    }

    /**
     * @param string $payload The payload must be the message content of the Ping
     * @return Frame
     */
    public function createPongFrame(string $payload): Frame
    {
        return $this->newFrame(
            payload: $payload,
            frameTypeEnum: FrameTypeEnum::Pong,
            writeMask: false
        );
    }
}
