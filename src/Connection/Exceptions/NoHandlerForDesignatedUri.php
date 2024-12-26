<?php

namespace Brash\Websocket\Connection\Exceptions;

final class NoHandlerForDesignatedUri extends \Exception
{
    public function __construct(string $uri) {
        parent::__construct(sprintf('No handler for request URI %s.', $uri));
    }
}

