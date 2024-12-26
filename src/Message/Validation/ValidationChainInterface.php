<?php

declare(strict_types=1);

namespace Brash\Websocket\Message\Validation;

use Brash\Websocket\Frame\Frame;
use Brash\Websocket\Message\Message;

interface ValidationChainInterface
{
    public function validate(Message $message, Frame $frame): ValidationResult;

    public function setNext(ValidationChainInterface $next);
}
