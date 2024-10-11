<?php

namespace Kit\Websocket\Message\Validation;
use Kit\Websocket\Frame\Frame;
use Kit\Websocket\Message\Message;


abstract class AbstractMessageValidator implements ValidationChainInterface
{

    protected ?ValidationChainInterface $nextHandler = null;

    public function validate(Message $message, Frame $frame): ValidationResult
    {
        return $this->nextHandler?->validate($message, $frame) ?? new ValidationResult(
            successfulMessage: $message
        );
    }

    public function setNext(ValidationChainInterface $next): ValidationChainInterface
    {
        $this->nextHandler = $next;

        return $next;
    }
}
