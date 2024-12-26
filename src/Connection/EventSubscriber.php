<?php

declare(strict_types=1);

namespace Brash\Websocket\Connection;

use Brash\Websocket\Connection\Events\OnConnectionErrorInterface;
use Brash\Websocket\Connection\Events\OnConnectionOpenInterface;
use Brash\Websocket\Connection\Events\OnDisconnecedConnectiontInterface;
use Brash\Websocket\Events\OnDataReceivedEvent;
use Brash\Websocket\Events\OnDisconnectEvent;
use Brash\Websocket\Events\OnNewConnectionOpenEvent;
use Brash\Websocket\Events\OnUpgradeEvent;
use Brash\Websocket\Events\OnWebSocketExceptionEvent;
use Brash\Websocket\Events\Protocols\ListenerProvider;
use Brash\Websocket\Exceptions\WebSocketException;
use Brash\Websocket\Handlers\OnUpgradeHandler;
use Brash\Websocket\Message\Handlers\CloseFrameHandler;
use Brash\Websocket\Message\Handlers\PingFrameHandler;
use Brash\Websocket\Message\Message;
use Brash\Websocket\Message\Protocols\MessageHandlerInterface;

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

        $this->listenerProvider->addListener(
            OnNewConnectionOpenEvent::class,
            fn(OnNewConnectionOpenEvent $event) => $handler->onOpen($event->connection)
        );
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

        $this->listenerProvider->addListener(
            OnDisconnectEvent::class,
            fn(OnDisconnectEvent $event) => $handler->onDisconnect($event->connection)
        );
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

        $this->listenerProvider->addListener(
            OnWebSocketExceptionEvent::class,
            fn(OnWebSocketExceptionEvent $event) => $handler->onError($event->webSocketException, $event->connection)
        );
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
