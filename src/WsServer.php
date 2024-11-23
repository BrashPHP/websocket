<?php

declare(strict_types=1);

namespace Kit\Websocket;

use Kit\Websocket\Config\Config;
use Kit\Websocket\Connection\DataHandlerFactory;
use Kit\Websocket\Connection\EventSubscriber;
use Kit\Websocket\Events\Protocols\ListenerProvider;
use Kit\Websocket\Events\Protocols\PromiseEventDispatcher;
use Kit\Websocket\Frame\FrameFactory;
use Kit\Websocket\Message\MessageWriter;
use Kit\Websocket\Message\Protocols\ConnectionHandlerInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use React\Socket\ConnectionInterface;
use React\Socket\ServerInterface;
use Kit\Websocket\Connection\Connection;
use SplObjectStorage;

class WsServer
{
    private readonly SplObjectStorage $connections;
    private ServerInterface $server;
    private readonly EventDispatcherInterface $eventDispatcher;
    private readonly EventSubscriber $subscriber;
    private LoggerInterface $logger;
    private LoopInterface $loop;

    public function __construct(
        private readonly int $port,
        private readonly string $host = '127.0.0.1',
        private Config $config = new Config(),
        ?LoopInterface $loop = null,
        ?LoggerInterface $logger = null,
        ?ServerInterface $server = null
    ) {
        $this->connections = new SplObjectStorage();
        $this->logger = $logger ?? (function (): LoggerInterface{
            $log = new Logger('cli-ws');
            $log->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG)); // <<< uses a stream
            return $log;
        })();
        $this->loop = $loop ?? Loop::get();
        $this->server = $server ?? new \React\Socket\TcpServer(
            uri: sprintf("%s:%d", $this->host, $this->port),
            loop: $this->loop
        );
        $listenerProvider = new ListenerProvider();
        $this->eventDispatcher = new PromiseEventDispatcher($listenerProvider);
        $this->subscriber = new EventSubscriber(
            $listenerProvider,
            new DataHandlerFactory(
                $this->config,
                $this->loop
            )
        );
    }

    public function setConnectionHandler(ConnectionHandlerInterface $connectionHandlerInterface): static
    {
        $this->subscriber->onNewConnection($connectionHandlerInterface);
        $this->subscriber->onMessageReceived($connectionHandlerInterface);
        $this->subscriber->onDisconnect($connectionHandlerInterface);
        $this->subscriber->onError($connectionHandlerInterface);

        return $this;
    }

    public function start()
    {
        $this->logger->info("Just started");

        if ($this->config->sslConfig) {
            $serverFactory = new SslServerFactory();
            $this->server = $serverFactory->createServer($this->server, $this->loop, $this->config->sslConfig);
            $this->logger->info('Enabled ssl');
        }

        $this->server->on(
            'connection',
            function (ConnectionInterface $connectionInterface): void {
                $this->newConnection($connectionInterface);
            }
        );

        $this->logger->info(message: sprintf("Listening on {$this->host}:{$this->port}"));

        $this->loop->run();
    }
    private function newConnection(ConnectionInterface $socketStream)
    {
        $this->logger->debug("Socket Stream: {$socketStream->getRemoteAddress()}");
        
        $connection = new Connection(
            eventDispatcher: $this->eventDispatcher,
            messageWriter: new MessageWriter(
                frameFactory: new FrameFactory(maxPayloadSize: $this->config->maxPayloadSize),
                socket: $socketStream,
                writeMasked: $this->config->writeMasked
            ),
            ip: $socketStream->getRemoteAddress(),
            logger: $this->logger,
        );

        $socketStream->on('data', $connection->onMessage(...));
        $socketStream->once('end', fn() => $connection->onEnd());
        $socketStream->on('error', fn($error) => $connection->onError($error));

        $socketStream->on('end', fn() => $this->onDisconnect(connection: $connection));

        $connection->getLogger()->info(sprintf('Ip "%s" establish connection', $connection->getIp()));
        $this->connections->attach($connection);
    }

    private function onDisconnect(Connection $connection)
    {
        if ($this->connections->offsetExists($connection)) {
            $this->connections->detach($connection);
        } else {
            $this->logger->critical('No connection found in the server connection list, impossible to delete the given connection id. Something wrong happened');
        }

        $connection->getLogger()->info(sprintf('Ip "%s" left connection', $connection->getIp()));
    }

    public function setConfig(Config|array $config): static
    {
        $this->config = is_array($config) ? Config::createFromArray($config) : $config;

        return $this;
    }

    public function setLogger(LoggerInterface $logger): static
    {
        $this->logger = $logger;

        return $this;
    }

    public function setLoop(LoopInterface $loop): static
    {
        $this->loop = $loop;

        return $this;
    }

    public function setSocketServer(ServerInterface $server): static
    {
        $this->server = $server;

        return $this;
    }
}
