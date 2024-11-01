<?php

namespace Kit\Websocket\Http\Exceptions;

final class BadUpgradeException extends \Exception
{
    public function __construct(string $requestString) {
        parent::__construct(sprintf("The request is not a websocket upgrade request, received:\n%s", $requestString));
    }
}


