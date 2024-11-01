<?php


namespace Kit\Websocket\Connection\Exceptions;
use Kit\Websocket\Exceptions\WebSocketException;

final class FailedWriteException extends \RuntimeException
{
    public function __construct(WebSocketException $ex)
    {
        parent::__construct($ex);
    }
}
