<?php

declare(strict_types=1);

namespace Kit\Websocket\Frame;

use Kit\Websocket\Frame\Enums\CloseFrameEnum;
use Kit\Websocket\Frame\Enums\FrameTypeEnum;
use Kit\Websocket\Frame\Exceptions\IncompleteFrameException;
use Kit\Websocket\Frame\FrameBuilder;
use Kit\Websocket\Result\Result;
use function Kit\Websocket\functions\intToBinaryString;

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
    private FrameBuilder $frameBuilder;

    /**
     * (0.5MiB) Adjust if needed.
     */
    public const int MAX_PAYLOAD_SIZE = 524288;


    public function __construct(private int $maxPayloadSize = 524288)
    {
        $this->frameBuilder = new FrameBuilder(maxPayloadSize: $maxPayloadSize);
    }

    /**
     * Returns a new frame if everything succeeds, otherwise throws an exception
     */
    public function newFrameFromRawData(string $rawData): Frame|IncompleteFrameException
    {
        return $this->frameBuilder->build($rawData);
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
