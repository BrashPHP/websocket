<?php


declare(strict_types=1);

namespace Kit\Websocket\Message\Protocols;

use Kit\Websocket\Connection\Events\OnConnectionErrorInterface;
use Kit\Websocket\Connection\Events\OnConnectionOpenInterface;
use Kit\Websocket\Connection\Events\OnDisconnecedConnectiontInterface;

interface ConnectionHandlerInterface extends
    MessageHandlerInterface,
    OnDisconnecedConnectiontInterface,
    OnConnectionOpenInterface,
    OnConnectionErrorInterface
{
}
