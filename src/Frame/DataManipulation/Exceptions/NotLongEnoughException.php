<?php

namespace Brash\Websocket\Frame\DataManipulation\Exceptions;

class NotLongEnoughException extends \InvalidArgumentException
{
    public function __construct() {
        parent::__construct('The frame is not long enough.');
    }
}
