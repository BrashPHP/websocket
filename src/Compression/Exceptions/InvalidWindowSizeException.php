<?php

namespace Kit\Websocket\Compression\Exceptions;

class InvalidWindowSizeException extends \RuntimeException
{
    public function __construct(string $field) {
        parent::__construct("Invalid window size in header. {$field} should be greater than 8 and lesser than 16 bits");
    }
}
