<?php

declare(strict_types=1);

namespace Brash\Websocket\Frame\DataManipulation\Exceptions;

final class PhpByteLimitationException extends \InvalidArgumentException
{
    public function __construct()
    {
        parent::__construct(
            message:
            'PHP limitation: retrieving more than 7 bytes will result in a negative value due to lack of unsigned integers.'
        );
    }
}
