<?php

declare(strict_types=1);

namespace Kit\Websocket\Frame\FrameValidation;

use Kit\Websocket\Frame\Exceptions\ControlFrameException;
use Kit\Websocket\Frame\Exceptions\TooBigControlFrameException;
use Kit\Websocket\Frame\Frame;

final class ControlFrameValidation implements FrameOpcodeValidatorInterface
{
    public const int MAX_CONTROL_FRAME_SIZE = 125;

    public function validate(Frame $frame): void{
        if ($frame->isControlFrame()) {
            if (!$frame->isFinal()) {
                throw new ControlFrameException('The frame cannot be fragmented');
            }

            if ($frame->getFramePayload()->getPayloadLength() > self::MAX_CONTROL_FRAME_SIZE) {
                throw new TooBigControlFrameException('A control frame cannot be larger than 125 bytes.');
            }
        }
    }
    
}

