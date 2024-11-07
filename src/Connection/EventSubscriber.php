<?php

declare(strict_types=1);

namespace Kit\Websocket\Connection;

use Kit\Websocket\Connection\Events\OnConnectionErrorInterface;
use Kit\Websocket\Connection\Events\OnConnectionOpenInterface;
use Kit\Websocket\Connection\Events\OnDisconnecedConnectiontInterface;
use Kit\Websocket\Events\OnDataReceivedEvent;
use Kit\Websocket\Events\OnDisconnectEvent;
use Kit\Websocket\Events\OnNewConnectionOpenEvent;
use Kit\Websocket\Events\OnUpgradeEvent;
use Kit\Websocket\Events\OnWebSocketExceptionEvent;
use Kit\Websocket\Events\Protocols\ListenerProvider;
use Kit\Websocket\Exceptions\WebSocketException;
use Kit\Websocket\Handlers\OnUpgradeHandler;
use Kit\Websocket\Message\Handlers\CloseFrameHandler;
use Kit\Websocket\Message\Handlers\PingFrameHandler;
use Kit\Websocket\Message\Message;
use Kit\Websocket\Message\Protocols\MessageHandlerInterface;

final readonly class EventSubscriber
{
    public function __construct(
        private ListenerProvider $listenerProvider,
        private DataHandlerFactory $dataHandlerFactory
    ) {
        $this->onUpgradeHandler();
        $this->dataHandlerFactory->appendMessageHandler(new CloseFrameHandler());
        $this->dataHandlerFactory->appendMessageHandler(new PingFrameHandler());
    }

    public function onNewConnection(OnConnectionOpenInterface|\Closure $newConnectionHandler): void
    {
        $handler = $newConnectionHandler;
        if (is_callable($newConnectionHandler)) {
            $handler = new class ($newConnectionHandler) implements OnConnectionOpenInterface {
                public function __construct(private readonly \Closure $closure)
                {
                }

                public function onOpen(Connection $connection): void
                {
                    $this->closure->call($this, $connection);
                }
            };
        }

        $this->listenerProvider->addListener(OnNewConnectionOpenEvent::class, $handler);
    }

    public function onDisconnect(OnDisconnecedConnectiontInterface|\Closure $disconnectionHandler): void
    {
        $handler = $disconnectionHandler;
        if (is_callable($disconnectionHandler)) {
            $handler = new class ($disconnectionHandler) implements OnDisconnecedConnectiontInterface {
                public function __construct(private readonly \Closure $closure)
                {
                }

                public function onDisconnect(Connection $connection): void
                {
                    $this->closure->call($this, $connection);
                }
            };
        }

        $this->listenerProvider->addListener(OnDisconnectEvent::class, $handler);
    }

    public function onError(OnConnectionErrorInterface|\Closure $disconnectionHandler): void
    {
        $handler = $disconnectionHandler;
        if (is_callable($disconnectionHandler)) {
            $handler = new class ($handler) implements OnConnectionErrorInterface {
                public function __construct(private readonly \Closure $closure)
                {
                }

                public function onError(WebSocketException $e, Connection $connection): void
                {
                    $this->closure->call($this, $e, $connection);
                }
            };
        }

        $this->listenerProvider->addListener(OnWebSocketExceptionEvent::class, $handler);
    }

    public function onMessageReceived(MessageHandlerInterface|\Closure $messageHandlerInterface)
    {
        $handler = $messageHandlerInterface;
        if (is_callable($messageHandlerInterface)) {
            $handler = new class ($handler) implements MessageHandlerInterface {
                public function __construct(private readonly \Closure $closure)
                {
                }

                public function handle(Message $message, Connection $connection): void
                {
                    $this->closure->call($this, $message, $connection);
                }
                public function hasSupport(Message $message): bool
                {
                    return true;
                }
            };
        }

        $this->dataHandlerFactory->appendMessageHandler(messageHandlerInterface: $handler);

        $this->listenerProvider->addListener(
            OnDataReceivedEvent::class,
            new DataHandlerAdapter(
                $this->dataHandlerFactory
            )
        );
    }

    private function onUpgradeHandler()
    {
        $this->listenerProvider->addListener(OnUpgradeEvent::class, new OnUpgradeHandler());
    }
}
