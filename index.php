<?php

use Kit\Websocket\Config\Config;
use Kit\Websocket\Message\Protocols\AbstractTextMessageHandler;
use Kit\Websocket\WsServer;
use React\EventLoop\Loop;
use React\Promise\Promise;
use function React\Async\{await, async};
use function Kit\Websocket\functions\println;

require_once 'vendor/autoload.php';

$server = new WsServer(1337, config: new Config(
    prod: false
));
$server->setConnectionHandler(
    connectionHandlerInterface: new class extends AbstractTextMessageHandler {
    public function handleTextData(string $data, Kit\Websocket\Connection\Connection $connection): void
    {
        $connection->getLogger()->debug("IP" . ":" . $connection->getIp() . PHP_EOL);
        $connection->getLogger()->debug("Data: " . $data . PHP_EOL);
        $connection->writeText(strtoupper($data));
    }
    }
);
$server->start();
