<?php


declare(strict_types=1);

namespace Brash\Websocket\Message\Protocols;

use Brash\Websocket\Connection\Events\OnConnectionErrorInterface;
use Brash\Websocket\Connection\Events\OnConnectionOpenInterface;
use Brash\Websocket\Connection\Events\OnDisconnecedConnectiontInterface;

interface ConnectionHandlerInterface extends
    MessageHandlerInterface,
    OnDisconnecedConnectiontInterface,
    OnConnectionOpenInterface,
    OnConnectionErrorInterface
{
}
