<?php

namespace Tests\Unit\Handlers;
use Kit\Websocket\Events\OnUpgradeEvent;
use Kit\Websocket\Events\Protocols\EventDispatcher;
use Kit\Websocket\Events\Protocols\ListenerProvider;
use Kit\Websocket\Handlers\OnUpgradeHandler;
use Psr\Http\Message\ServerRequestInterface;
use React\Socket\ConnectionInterface;



test('should receive event correctly', function (): void {
    $listenerProvider = new ListenerProvider();
    /** @var ConnectionInterface|\Mockery\MockInterface */
    $connectionInterfaceMock = mock(ConnectionInterface::class);
    $connectionInterfaceMock->shouldReceive('write')->with(
        'HTTP/1.1 101 Switching Protocols\r\nUpgrade: websocket\r\nConnection: '
        . 'Upgrade\r\nsec-webSocket-accept: nl8AwnZur2jxjO83O32/6MVk6Pw=\r\n\r\n'
    );
    $handler = new OnUpgradeHandler();

    $listenerProvider->addListener(OnUpgradeEvent::class, $handler);
    /** @var ServerRequestInterface|\Mockery\MockInterface */
    $mockServerRequest = mock(ServerRequestInterface::class);
    $mockServerRequest->shouldReceive('getHeader')->withAnyArgs()->andReturn(['any-id']);
    $dispatcher = new EventDispatcher($listenerProvider);
    $event = $dispatcher->dispatch(new OnUpgradeEvent($mockServerRequest));

    expect($event)->toBeObject();
});
