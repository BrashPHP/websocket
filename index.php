<?php

use Kit\Websocket\Config\Config;
use Kit\Websocket\Connection\Connection;
use Kit\Websocket\Message\Protocols\AbstractTextMessageHandler;
use Kit\Websocket\WsServer;

require_once 'vendor/autoload.php';

$server = new WsServer(1337, host: '0.0.0.0', config: new Config(
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
        $broadcast = array_filter($this->connections, fn($conn) => $conn !== $connection);

        foreach ($broadcast as $conn) {
            $conn->writeText($data);
        }
    }
    }
);
$server->start();

