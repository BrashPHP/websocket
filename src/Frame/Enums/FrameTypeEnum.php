<?php

declare(strict_types=1);

namespace Brash\Websocket\Frame\Enums;


/**
 * Defines the opcode used in the frame.
 * The opcode is the second part of the very first byte.
 * Thus it is possible to extract it via a simple `$fullbyte & 15` bitwise operation.
 */
enum FrameTypeEnum: int
{
    case Continuation = 0x00;
    case Text = 0x01;
    case Binary = 0x02;
    case Close = 0x08;
    case Ping = 0x09;
    case Pong = 0x0A;

    public function isControlFrame(): bool
    {
        return $this->value >= 0x08;
    }

    public function isOperation(): bool
    {
        return $this === FrameTypeEnum::Text || $this === FrameTypeEnum::Binary;
    }
}
