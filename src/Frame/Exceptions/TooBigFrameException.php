<?php

declare(strict_types=1);

namespace Kit\Websocket\Frame\Exceptions;

use Exception;

readonly class TooBigFrameException extends Exception
{
    public function __construct(
        public int $maxLength,
        public string $message = 'The frame is too big to be processed.'
    ) {
    }

}
