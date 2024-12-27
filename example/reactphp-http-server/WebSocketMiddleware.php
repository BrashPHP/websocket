<?php

namespace Voryx\WebSocketMiddleware;

use Brash\Websocket\Compression\CompressionDeflaterDetector;
use Brash\Websocket\Config\Config;
use Brash\Websocket\Connection\ConnectionFactory;
use Brash\Websocket\Message\Protocols\AbstractTextMessageHandler;
use Brash\Websocket\Connection\ConnectionHandshake;
use Brash\Websocket\Connection\DataHandlerFactory;
use Brash\Websocket\Connection\EventSubscriber;
use Brash\Websocket\Events\Protocols\ListenerProvider;
use Brash\Websocket\Events\Protocols\PromiseEventDispatcher;
use Brash\Websocket\Message\Protocols\ConnectionHandlerInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use React\Http\Message\Response;
use React\Stream\CompositeStream;
use React\Stream\ThroughStream;
use React\Http\HttpServer;

require_once __DIR__ . '/../../vendor/autoload.php';

final class WebSocketMiddleware
{
    private readonly EventDispatcherInterface $eventDispatcher;
    private readonly EventSubscriber $subscriber;
    private LoggerInterface $logger;

    public function __construct(
        private ConnectionHandlerInterface $connectionHandler,
        private ?LoopInterface $loop = null,
        private array $paths = [],
        private ConnectionHandshake $hanshaker = new ConnectionHandshake(),
        private Config $config = new Config(prod: false),
        ?LoggerInterface $logger = null,
    ) {
        $listenerProvider = new ListenerProvider();
        $this->eventDispatcher = new PromiseEventDispatcher($listenerProvider);
        $this->subscriber = new EventSubscriber(
            $listenerProvider,
            new DataHandlerFactory(
                $this->config,
                $this->loop ?? Loop::get()
            )
        );
        $this->logger = $logger ?? (function (): LoggerInterface{
            $log = new Logger('cli-ws');
            $log->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG)); // <<< uses a stream
            return $log;
        })();

        $this->subscriber->onNewConnection($connectionHandler);
        $this->subscriber->onMessageReceived($connectionHandler);
        $this->subscriber->onDisconnect($connectionHandler);
        $this->subscriber->onError($connectionHandler);
    }

    public function execute(ServerRequestInterface $request, callable $next = null)
    {
        // check path at some point - for now we just go go ws
        if (count($this->paths) > 0 && !in_array($request->getUri()->getPath(), $this->paths)) {
            return $next === null ? new Response(404) : $next($request);
        }

        $response = $this->hanshaker->handshake($request);

        if ($response->getStatusCode() !== 101) {
            return $next === null ? new Response(404) : $next($request);
        }

        $compressionDetector = new CompressionDeflaterDetector();
        $compression = $compressionDetector->detect($request);

        if ($compression) {
            $response = $response->withAddedHeader(
                'Sec-WebSocket-Extensions',
                $compression->compressionConf->getConfAsStringHeader()
            );
        }

        $inStream = new ThroughStream();
        $outStream = new ThroughStream();
        $stream = new CompositeStream(
            $outStream,
            $inStream
        );

        $response = new Response(
            $response->getStatusCode(),
            $response->getHeaders(),
            $stream
        );

        $connectionFactory = new ConnectionFactory();

        $conn = $connectionFactory->createConnection(
            connectionInterface: new CompositeStream($inStream, $outStream),
            logger: $this->logger,
            eventDispatcher: $this->eventDispatcher,
            config: $this->config,
            ip: $request->getServerParams()['REMOTE_ADDR']
        );

        $conn->completeHandshake();
        $conn->setCompression($compression);

        $inStream->on('data', $conn->onMessage(...));
        $stream->once('end', fn() => $conn->onEnd());
        $inStream->on('error', fn($error) => $conn->onError($error));

        return $response;
    }
}

$connectionHandlerInterface = new class extends AbstractTextMessageHandler {
    private array $connections;
    public function __construct()
    {
        $this->connections = [];
    }

    public function onOpen(\Brash\Websocket\Connection\Connection $connection): void
    {
        $this->connections[] = $connection;
    }

    public function handleTextData(string $data, \Brash\Websocket\Connection\Connection $connection): void
    {
        $connection->getLogger()->debug("IP" . ":" . $connection->getIp() . PHP_EOL);
        $connection->getLogger()->debug("Data: " . $data . PHP_EOL);
        $broadcast = array_filter($this->connections, fn($conn) => $conn !== $connection);

        foreach ($broadcast as $conn) {
            $conn->writeText($data);
        }
        $connection->writeText(strtoupper($data));
    }
};

$ws = new WebSocketMiddleware($connectionHandlerInterface);

$socket = new \React\Socket\SocketServer($argv[1] ?? '0.0.0.0:1337',);

$server = new HttpServer($ws->execute(...));

$server->listen($socket);

echo 'Listening on ' . str_replace('tcp:', 'http:', $socket->getAddress()) . PHP_EOL;