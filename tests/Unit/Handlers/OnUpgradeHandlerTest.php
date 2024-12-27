<?php

namespace Tests\Unit\Handlers;
use Brash\Websocket\Config\Config;
use Brash\Websocket\Connection\Connection;
use Brash\Websocket\Connection\ConnectionFactory;
use Brash\Websocket\Events\OnUpgradeEvent;
use Brash\Websocket\Events\Protocols\EventDispatcher;
use Brash\Websocket\Events\Protocols\ListenerProvider;
use Brash\Websocket\Handlers\OnUpgradeHandler;
use Brash\Websocket\Http\RequestFactory;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\NullLogger;
use React\Socket\ConnectionInterface;



test('should receive event correctly', function (): void {
    $requestString = "GET /chat HTTP/1.1\r\nHost: example.com:8000\r\nUpgrade: websocket" .
        "\r\nConnection: Upgrade\r\nSec-WebSocket-Key: any-id==\r\nSec-WebSocket-Version: 13";
    $request = RequestFactory::createRequest($requestString);
    $handler = new OnUpgradeHandler();
    /** @var ConnectionInterface|\Mockery\MockInterface */
    $connectionInterfaceMock = mock(ConnectionInterface::class);
    $connectionInterfaceMock->shouldReceive('write')->with(
        "HTTP/1.1 101 Switching Protocols\r\nUpgrade: websocket\r\nConnection: "
        . "Upgrade\r\nSec-WebSocket-Accept: ueUhCZHX7AOVp2w1LVjpv4Tn05s=\r\n\r\n"
    );
    $connectionFactory = new ConnectionFactory();
    $conn = $connectionFactory->createConnection(
        $connectionInterfaceMock,
        new NullLogger(),
        new EventDispatcher(new ListenerProvider()),
        new Config(prod: false),
        '0.0.0.1'
    );

    expect($handler->execute(new OnUpgradeEvent($request, $conn)))->not()->toThrow(\Exception::class);

});
