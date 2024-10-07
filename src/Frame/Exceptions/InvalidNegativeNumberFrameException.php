<?php

declare(strict_types=1);

namespace Kit\Websocket\Frame\Exceptions;

/**
 * Exception thrown when a frame is a negative number.
 */
final class InvalidNegativeNumberFrameException extends \InvalidArgumentException
{
    public function __construct()
    {
        parent::__construct(message: 'The frame cannot be a negative number');
    }
}
