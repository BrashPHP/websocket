<?php

declare(strict_types=1);

namespace Brash\Websocket\Frame\FrameValidation;

use Brash\Websocket\Frame\Enums\FrameTypeEnum;
use Brash\Websocket\Frame\Exceptions\ProtocolErrorException;
use Brash\Websocket\Frame\Frame;
use Brash\Websocket\Frame\Protocols\FrameOpcodeValidatorInterface;

final class ValidationUponOpCode implements FrameOpcodeValidatorInterface
{
    #[\Override]
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
