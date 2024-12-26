<?php

declare(strict_types=1);

namespace Brash\Websocket\Frame\Protocols;

use Brash\Websocket\Frame\Exceptions\ProtocolErrorException;
use Brash\Websocket\Frame\Frame;

interface FrameOpcodeValidatorInterface{
    public function validate(Frame $frame): ?ProtocolErrorException;
}
