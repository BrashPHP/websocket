<?php

use Brash\Websocket\Message\MessageWriter;
use Brash\Websocket\WsServer;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\EventLoop\Loop;
use React\Http\Message\Response;
use React\Stream\CompositeStream;
use React\Stream\ThroughStream;

require_once __DIR__ . '/../../vendor/autoload.php';

$chat = new ThroughStream();

$websocketServer = new WsServer(port: 1337);

$http = new React\Http\HttpServer(function (ServerRequestInterface $request) use ($chat, $websocketServer): Response {
    // user stream forwards chat data and accepts incoming data
    $out = $chat->pipe(new ThroughStream());
    $in = new ThroughStream();
    $stream = new CompositeStream(
        $out,
        $in
    );

    // assign some name for this new connection
    $username = 'user' . mt_rand();

    // send anything that is received to the whole channel
    $in->on('data', function ($data) use ($username, $chat): void {
        $data = trim(preg_replace('/[^\w \.\,\-\!\?]/u', '', $data));

        $chat->write($username . ': ' . $data . PHP_EOL);
    });

    new MessageWriter(
        
    );

    // say hello to new user
    Loop::addTimer(0, function () use ($chat, $username, $out): void {
        $out->write('Welcome to this chat example, ' . $username . '!' . PHP_EOL);
        $chat->write($username . ' joined' . PHP_EOL);
    });

    // send goodbye to channel once connection closes
    $stream->on('close', function () use ($username, $chat) {
        $chat->write($username . ' left' . PHP_EOL);
    });

    return new Response(
        StatusCodeInterface::STATUS_SWITCHING_PROTOCOLS,
        ['Upgrade' => 'chat'],
        $stream
    );
});

$socket = new React\Socket\SocketServer(isset($argv[1]) ? $argv[1] : '0.0.0.0:0');
$http->listen($socket);

echo 'Listening on ' . str_replace('tcp:', 'http:', $socket->getAddress()) . PHP_EOL;
