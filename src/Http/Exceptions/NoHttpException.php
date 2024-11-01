<?php

namespace Kit\Websocket\Http\Exceptions;


class NoHttpException extends \Exception
{
    public function __construct(string|int $line)
    {
        parent::__construct(
            \sprintf(
                'The message is not an http request. "%s" received.',
                (string) $line
            )
        );
    }
}
