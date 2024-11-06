<?php

namespace Kit\Websocket;



use React\EventLoop\LoopInterface;
use React\Socket\ConnectionInterface;
use React\Socket\SocketServer;


class Server
{

    public function __construct(
        private readonly LoopInterface $loopInterface,
    ) {
        
    }

    public function run(SocketServer $socketServer)
    {
        $this->onReadableSocket($socketServer);
    }


    public function onReadableSocket(SocketServer $socketServer)
    {
        $socket = new \React\Socket\LimitingServer($socketServer, null);

        $socket->on('connection', function (ConnectionInterface $connection) use ($socket): void {
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
                    $connection->write($data);
                }
            });

            $connection->on('close', function () use ($connection): void {
                echo '[' . $connection->getRemoteAddress() . ' disconnected]' . PHP_EOL;
            });
        });
    }
}
