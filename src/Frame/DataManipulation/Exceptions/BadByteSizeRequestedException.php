<?php

declare(strict_types=1);

namespace Brash\Websocket\Frame\DataManipulation\Exceptions;

final class BadByteSizeRequestedException extends \InvalidArgumentException
{
    public function __construct(int $bytesAsked)
    {
        parent::__construct(
            message: sprintf(
                'Not more than 8 bytes (64bit) is supported by this method and you asked for %s bytes.',
                $bytesAsked
            )
        );
    }
}
