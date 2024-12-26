<?php

namespace Brash\Websocket\Config\Exceptions;

final class XdebugEnabledException extends \Exception
{
    public function __construct()
    {
        parent::__construct('xdebug is enabled, it\'s a performance issue. Disable that extension or specify "prod" option to false.');
    }
}
