<?php

declare(strict_types=1);

namespace Brash\Websocket\Frame\Exceptions;

use Brash\Websocket\Message\Exceptions\LimitationException;

class TooBigFrameException extends LimitationException
{
    public function __construct(
        public int $maxLength,
        public $message = 'The frame is too big to be processed.'
    ) {
    }

}
