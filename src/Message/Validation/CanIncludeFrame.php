<?php

declare(strict_types=1);

namespace Kit\Websocket\Message\Validation;
use Kit\Websocket\Frame\Enums\FrameTypeEnum;
use Kit\Websocket\Frame\Exceptions\ProtocolErrorException;
use Kit\Websocket\Frame\Frame;
use Kit\Websocket\Message\Message;

final class CanIncludeFrame extends AbstractMessageValidator
{
    public function validate(Message $message, Frame $frame): ValidationResult
    {
        $exception = $message->addFrame($frame);

        if (is_null($exception)) {
            return $this->nextHandler->validate($message, $frame);
        }
        
        return new ValidationResult(error: $exception);
    }
}
