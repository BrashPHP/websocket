<?php

namespace Kit\Websocket\DataManipulation\Exceptions;

final class InvalidBitException extends \InvalidArgumentException
{
    public function __construct(int $bitNumber)
    {

        parent::__construct(
            \sprintf(
                'The bit number %s is not a correct value for a byte (1-8 required).',
                $bitNumber
            )
        );
    }
}

