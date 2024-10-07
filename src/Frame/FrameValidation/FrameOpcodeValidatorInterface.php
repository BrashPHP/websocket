<?php

declare(strict_types=1);

namespace Kit\Websocket\Frame\FrameValidation;
use Kit\Websocket\Frame\Frame;

interface FrameOpcodeValidatorInterface{
    public function validate(Frame $frame): void;
}