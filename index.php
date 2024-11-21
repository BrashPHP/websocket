<?php

use Kit\Websocket\Config\Config;
use Kit\Websocket\Connection\Connection;
use Kit\Websocket\Message\Protocols\AbstractTextMessageHandler;
use Kit\Websocket\Watcher\Watch;
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
    /**
     * @var Connection[]
     */
    private array $connections;
    public function __construct()
    {
        $this->connections = [];
    }

    public function onOpen(Kit\Websocket\Connection\Connection $connection): void
    {
        $this->connections[] = $connection;
    }

    public function handleTextData(string $data, Kit\Websocket\Connection\Connection $connection): void
    {
        $connection->getLogger()->debug("IP" . ":" . $connection->getIp() . PHP_EOL);
        $connection->getLogger()->debug("Data: " . $data . PHP_EOL);
        foreach ($this->connections as $conn) {
            $conn->writeText(sprintf("%s ip says: %s", $connection->getIp(), $data));
        }
    }
    }
);
$server->start();

