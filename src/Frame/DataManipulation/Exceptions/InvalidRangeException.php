<?php

declare(strict_types=1);

namespace Brash\Websocket\Frame\DataManipulation\Exceptions;

final class InvalidRangeException extends \InvalidArgumentException
{
    public function __construct()
    {
        parent::__construct(
            message: 'The "to" value must be greater than "from".'
        );
    }
}
