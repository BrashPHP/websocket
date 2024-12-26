<?php

declare(strict_types=1);

namespace Brash\Websocket\Frame\FrameValidation;

use Brash\Websocket\Frame\Exceptions\ProtocolErrorException;
use Brash\Websocket\Frame\Frame;
use Brash\Websocket\Frame\Protocols\FrameOpcodeValidatorInterface;

final class CloseFrameValidation implements FrameOpcodeValidatorInterface
{
    #[\Override]
    public function validate(Frame $frame): ?ProtocolErrorException
    {
        if($frame->getFramePayload()->getPayloadLength() === 1) {
            return new ProtocolErrorException(
                message:
                'The close frame cannot be only 1 byte as the close code MUST be sent as 2 bytes unsigned int.'
            );
        }

        return null;
    }
}
