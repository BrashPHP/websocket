<?php

declare(strict_types=1);

namespace Kit\Websocket\Frame\FrameValidation;

use Kit\Websocket\Frame\Enums\FrameTypeEnum;
use Kit\Websocket\Frame\Exceptions\ProtocolErrorException;
use Kit\Websocket\Frame\Frame;
use Kit\Websocket\Frame\Protocols\FrameOpcodeValidatorInterface;

final class ValidationUponOpCode implements FrameOpcodeValidatorInterface
{
    public function validate(Frame $frame): ?ProtocolErrorException
    {
        $validator = null;

        if ($frame->isControlFrame()) {
            $validator = new ControlFrameValidation();
        } elseif ($frame->getOpcode() === FrameTypeEnum::Close) {
            $validator = new CloseFrameValidation();
        }

        return $validator?->validate($frame);
    }
}
