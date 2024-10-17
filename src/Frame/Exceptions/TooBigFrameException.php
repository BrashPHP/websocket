<?php

declare(strict_types=1);

namespace Kit\Websocket\Frame\Exceptions;

use Kit\Websocket\Message\Exceptions\LimitationException;

class TooBigFrameException extends LimitationException
{
    public function __construct(
        public int $maxLength,
        public $message = 'The frame is too big to be processed.'
    ) {
    }

}
