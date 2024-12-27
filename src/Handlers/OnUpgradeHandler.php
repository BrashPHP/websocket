<?php

namespace Brash\Websocket\Handlers;

use Brash\Websocket\Compression\CompressionDeflaterDetector;
use Brash\Websocket\Connection\ConnectionHandshake;
use Brash\Websocket\Events\OnNewConnectionOpenEvent;
use Brash\Websocket\Events\OnUpgradeEvent;
use Brash\Websocket\Events\OnWebSocketExceptionEvent;
use Brash\Websocket\Events\Protocols\Event;
use Brash\Websocket\Events\Protocols\ListenerInterface;
use Brash\Websocket\Frame\Enums\CloseFrameEnum;
use Brash\Websocket\Utilities\HandshakeResponder;

final readonly class OnUpgradeHandler implements ListenerInterface
{
    private ConnectionHandshake $handshaker;
    
    public function __construct()
    {
        $this->handshaker = new ConnectionHandshake();
    }

    /**
     * @param OnUpgradeEvent $event
     *
     * @return \React\Promise\PromiseInterface
     */
    #[\Override]
    public function execute(Event $event): void
    {
        $connection = $event->connection;
        $request = $event->request;
        
        $response = $this->handshaker->handshake($request);

        if ($response instanceof \Exception) {
            $connection->close(CloseFrameEnum::CLOSE_NORMAL);
            $connection->getLogger()->notice('WebSocket error: ' . $response->getMessage());
            $connection->getEventDispatcher()->dispatch(new OnWebSocketExceptionEvent(
                $response,
                $connection
            ));
        }

        $compressionDetector = new CompressionDeflaterDetector();
        $compression = $compressionDetector->detect($request);
        $connection->setCompression($compression);

        if ($connection->isCompressionEnabled()) {
            $response = $response->withAddedHeader(
                'Sec-WebSocket-Extensions',
                $compression->compressionConf->getConfAsStringHeader()
            );
        }

        $responder = new HandshakeResponder();
        $rawResponse = $responder->prepareHandshakeResponse(
            $response->getHeaderLine("Sec-WebSocket-Accept")
        );
        if ($connection->isCompressionEnabled()) {
            $rawResponse = $connection->getCompression()->attachToStringResponse($rawResponse);
        }

        $connection->getSocketWriter()->write($rawResponse);
        $connection->getEventDispatcher()->dispatch(new OnNewConnectionOpenEvent($connection));
        $connection->completeHandshake();
    }
}
