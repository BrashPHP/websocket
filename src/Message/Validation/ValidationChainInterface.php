<?php

declare(strict_types=1);

namespace Kit\Websocket\Message\Validation;

use Kit\Websocket\Frame\Frame;
use Kit\Websocket\Message\Message;

interface ValidationChainInterface
{
    public function validate(Message $message, Frame $frame): ValidationResult;

    public function setNext(ValidationChainInterface $next);
}
