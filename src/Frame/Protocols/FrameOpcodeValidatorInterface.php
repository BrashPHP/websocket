<?php

declare(strict_types=1);

namespace Kit\Websocket\Frame\Protocols;

use Kit\Websocket\Frame\Exceptions\ProtocolErrorException;
use Kit\Websocket\Frame\Frame;

interface FrameOpcodeValidatorInterface{
    public function validate(Frame $frame): ?ProtocolErrorException;
}
