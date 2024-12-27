<?php

use Brash\Websocket\Config\Config;
use Brash\Websocket\Connection\Connection;
use Brash\Websocket\Message\Protocols\AbstractTextMessageHandler;
use Brash\Websocket\WsServer;

require_once __DIR__.'/../../vendor/autoload.php';

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

    public function onOpen(Brash\Websocket\Connection\Connection $connection): void
    {
        $this->connections[] = $connection;
    }

    public function handleTextData(string $data, Brash\Websocket\Connection\Connection $connection): void
    {
        $connection->getLogger()->debug("IP" . ":" . $connection->getIp() . PHP_EOL);
        $connection->getLogger()->debug("Data: " . $data . PHP_EOL);
        $broadcast = array_filter($this->connections, fn($conn) => $conn !== $connection);

        foreach ($broadcast as $conn) {
            $conn->writeText($data);
        }
        $connection->writeText(strtoupper($data));
    }
    }
);
$server->start();

