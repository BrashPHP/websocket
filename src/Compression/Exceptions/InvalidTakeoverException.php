<?php

namespace Brash\Websocket\Compression\Exceptions;

class InvalidTakeoverException extends \RuntimeException
{
    public function __construct(string $field) {
        parent::__construct("Invalid takeover header. {$field} must not have a value");
    }
}

