<?php
namespace Kit\Websocket\Frame\FrameValidation;
use Kit\Websocket\Frame\Enums\FrameTypeEnum;
use Kit\Websocket\Frame\Frame;

final class ValidationUponOpCode implements FrameOpcodeValidatorInterface
{
    public function validate(Frame $frame): void
    {
        $validator = null;

        if ($frame->isControlFrame()) {
            $validator = new ControlFrameValidation();
        } elseif ($frame->getOpcode() === FrameTypeEnum::Close) {
            $validator = new CloseFrameValidation();
        }

        $validator?->validate($frame);
    }
}
