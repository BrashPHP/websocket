<?php

declare(strict_types=1);

namespace Brash\Websocket\Frame\FrameValidation;

use Brash\Websocket\Frame\Exceptions\ControlFrameException;
use Brash\Websocket\Frame\Exceptions\ProtocolErrorException;
use Brash\Websocket\Frame\Exceptions\TooBigControlFrameException;
use Brash\Websocket\Frame\Frame;
use Brash\Websocket\Frame\Protocols\FrameOpcodeValidatorInterface;

final class ControlFrameValidation implements FrameOpcodeValidatorInterface
{
    public const int MAX_CONTROL_FRAME_SIZE = 125;

    #[\Override]
    public function validate(Frame $frame): ?ProtocolErrorException{
        if ($frame->isControlFrame()) {
            if (!$frame->isFinal()) {
                return new ControlFrameException('The frame cannot be fragmented');
            }

            if ($frame->getFramePayload()->getPayloadLength() > self::MAX_CONTROL_FRAME_SIZE) {
                return new TooBigControlFrameException('A control frame cannot be larger than 125 bytes.');
            }
        }

        return null;
    }
    
}

