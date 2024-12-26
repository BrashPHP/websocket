<?php

namespace Brash\Websocket\Frame\DataManipulation\Exceptions;

final class InvalidByteException extends \InvalidArgumentException
{
    public function __construct(int $byte)
    {
        parent::__construct(
            \sprintf(
                'The given integer %s is not a byte.',
                $byte
            )
        );
    }
}

