<?php

declare(strict_types=1);

namespace Kit\Websocket\Frame\FrameValidation;

use Kit\Websocket\Frame\Exceptions\ProtocolErrorException;
use Kit\Websocket\Frame\Frame;

final class CloseFrameValidation implements FrameOpcodeValidatorInterface
{
    public function validate(Frame $frame): void
    {
        if($frame->getFramePayload()->payloadLen === 1) {
            throw new ProtocolErrorException(
                message:
                'The close frame cannot be only 1 byte as the close code MUST be sent as 2 bytes unsigned int.'
            );
        }
    }
}
