<?php

use Kit\Websocket\Server;
use React\EventLoop\Loop;
use React\Socket\SocketServer;
use Revolt\EventLoop\React\Internal\EventLoopAdapter;

require_once "vendor/autoload.php";

Loop::set(EventLoopAdapter::get());

$loop = Loop::get();

$socket = new React\Socket\SocketServer($argv[1] ?? '127.0.0.1:0', loop: $loop);

$socket = new React\Socket\LimitingServer($socket, null);

$socket->on('connection', function (React\Socket\ConnectionInterface $connection) use ($socket): void {
    echo '[' . $connection->getRemoteAddress() . ' connected]' . PHP_EOL;

    // whenever a new message comes in
    $connection->on('data', function ($data) use ($connection, $socket): void {
        // ignore empty messages
        if ($data === '') {
            return;
        }

        // prefix with client IP and broadcast to all connected clients
        $data = trim(parse_url((string) $connection->getRemoteAddress(), PHP_URL_HOST), '[]') . ': ' . $data . PHP_EOL;
        foreach ($socket->getConnections() as $connection) {
            $connection->write(strtoupper($data));
        }
    });

    $connection->on('close', function () use ($connection): void {
        echo '[' . $connection->getRemoteAddress() . ' disconnected]' . PHP_EOL;
    });
});

$socket->on('error', function (Exception $e): void {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
});

echo 'Listening on ' . $socket->getAddress() . PHP_EOL;

$loop->run();