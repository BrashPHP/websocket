<?php

namespace Brash\Websocket\Compression\Exceptions;

class BadCompressionException extends \RuntimeException
{
    public function __construct(string $data, \Throwable $previous)
    {
        parent::__construct("Error when compressing data: $data. Reason {$previous->getMessage()}");
    }
}

