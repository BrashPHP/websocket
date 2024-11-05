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
use Kit\Websocket\Frame\Enums\FrameTypeEnum;
use Kit\Websocket\Handlers\OnUpgradeHandler;
use Kit\Websocket\Message\Protocols\MessageHandlerInterface;
use React\EventLoop\LoopInterface;

final class EventSubscriber
{
    public function __construct(
        private ListenerProvider $listenerProvider,
        private LoopInterface $loopInterface,
        private Config $config
    ) {
        $this->onUpgradeHandler();
    }

    public function onNewConnection(OnConnectionOpenInterface|\Closure $newConnectionHandler): void
    {
        $handler = $newConnectionHandler;
        if (is_callable($newConnectionHandler)) {
            $handler = new class ($newConnectionHandler) implements OnConnectionOpenInterface {
                public function __construct(private \Closure $closure)
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
                public function __construct(private \Closure $closure)
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
                public function __construct(private \Closure $closure)
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
                public function __construct(private \Closure $closure)
                {
                }

                public function handle(string $data, Connection $connection): void
                {
                    $this->closure->call($this, $data, $connection);
                }
                public function supportsFrame(FrameTypeEnum $opcode): bool
                {
                    return true;
                }
            };
        }

        $this->listenerProvider->addListener(
            OnDataReceivedEvent::class,
            new MessageHandlerAdapter(
                $handler,
                $this->config,
                $this->loopInterface
            )
        );
    }

    private function onUpgradeHandler()
    {
        $this->listenerProvider->addListener(OnUpgradeEvent::class, new OnUpgradeHandler());
    }
}
