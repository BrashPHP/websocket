<?php


namespace Brash\Websocket\Connection\Exceptions;
use Brash\Websocket\Exceptions\WebSocketException;

final class FailedWriteException extends \RuntimeException
{
    public function __construct(WebSocketException $ex)
    {
        parent::__construct($ex);
    }
}
