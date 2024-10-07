<?php

declare(strict_types=1);

namespace Kit\Websocket\Frame;

use Kit\Websocket\Frame\Enums\CloseFrameEnum;
use Kit\Websocket\Frame\Enums\FrameTypeEnum;
use Kit\Websocket\Frame\FrameBuilder;
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
    public function __construct(private int $maxPayloadSize = 524288)
    {
    }

    public function newFrame(string $payload, FrameTypeEnum $frameTypeEnum, bool $writeMask)
    {
        return FrameBuilder::createFromPayload(
            $payload,
            $frameTypeEnum,
            $writeMask
        );
    }

    /**
     * @param int    $status One of the close constant code of Frame class.
     * @param string $reason A little message that explain why closing.
     * @return Frame
     */
    public function createCloseFrame(int $status = CloseFrameEnum::CLOSE_NORMAL, string $reason = null): Frame
    {
        $content = intToBinaryString($status);
        if (null !== $reason) {
            $content .= $reason;
        }

        return FrameBuilder::createFromPayload($content, FrameTypeEnum::Close, false);
    }

    /**
     * @param string $payload The payload must be the message content of the Ping
     * @return Frame
     */
    public function createPongFrame(string $payload): Frame
    {
        return FrameBuilder::createFromPayload($payload, FrameTypeEnum::Pong, false);
    }
}
