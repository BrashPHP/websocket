<?php

namespace Kit\Websocket\Connection;

use Kit\Websocket\Compression\CompressionDeflaterDetector;
use Kit\Websocket\Events\OnNewConnectionOpenEvent;
use Kit\Websocket\Events\OnUpgradeEvent;
use Kit\Websocket\Events\OnWebSocketExceptionEvent;
use Kit\Websocket\Exceptions\WebSocketException;
use Kit\Websocket\Frame\Enums\CloseFrameEnum;
use Kit\Websocket\Http\Exceptions\BadUpgradeException;
use Kit\Websocket\Http\RequestFactory;
use Kit\Websocket\Http\Response;
use Kit\Websocket\Utilities\HandshakeResponder;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use React\Promise\PromiseInterface;

final readonly class ConnectionHandshake
{
    private CompressionDeflaterDetector $compressionDetector;
    private HandshakeResponder $handshakeResponder;

    public function __construct(private Connection $connection)
    {
        $this->compressionDetector = new CompressionDeflaterDetector();
        $this->handshakeResponder = new HandshakeResponder();
    }

    /**
     * Attempts to perform a handshake using the given request.
     *
     * @param RequestInterface $request
     * @return PromiseInterface
     */
    public function tryHandshakeFromRequest(RequestInterface $request): PromiseInterface
    {
        $this->validateRequest($request);
        $this->detectCompression($request);

        $connection = $this->connection;
        $eventDispatcher = $connection->getEventDispatcher();

        return $eventDispatcher
            ->dispatch(new OnUpgradeEvent($request))
            ->then(
                fn(string $acceptKey) => $this->prepareUpgradeResponse($acceptKey),
                fn(WebSocketException $exception) => $this->handleWebSocketException($exception)
            );
    }

    /**
     * Performs a handshake based on raw request data.
     *
     * @param string $data
     * @return void
     */
    public function handshake(string $data): void
    {
        $request = $this->tryCreateRequest($data);

        if (!$request) {
            return;
        }

        $this->tryHandshakeFromRequest($request)
            ->then(
                onFulfilled: fn(ResponseInterface $response) => $this->sendHandshakeResponse($response),
                onRejected: fn(WebSocketException $exception) => $this->handleWebSocketException($exception)
            );
    }

    /**
     * Creates a PSR-7 Request object from raw data.
     *
     * @param string $data
     * @return RequestInterface|null
     */
    private function tryCreateRequest(string $data): ?RequestInterface
    {
        try {
            $request = RequestFactory::createRequest($data);
            $this->validateRequest($request);
            $this->detectCompression($request);
            return $request;
        } catch (\Throwable $exception) {
            $this->sendErrorResponse($exception->getMessage());
            return null;
        }
    }

    /**
     * Validates a WebSocket handshake request.
     *
     * @param RequestInterface $request
     * @throws BadUpgradeException
     */
    private function validateRequest(RequestInterface $request): void
    {
        if (
            !$request->hasHeader('Sec-WebSocket-Key') ||
            !$request->hasHeader('Upgrade') ||
            strtolower($request->getHeaderLine('Upgrade')) !== 'websocket'
        ) {
            throw new BadUpgradeException('Invalid WebSocket upgrade request.');
        }
    }

    /**
     * Detects compression settings from the request and updates the connection.
     *
     * @param RequestInterface $request
     */
    private function detectCompression(RequestInterface $request): void
    {
        $this->connection->setCompression(
            serverCompressionContext: $this->compressionDetector->detect($request)
        );
    }

    /**
     * Prepares the handshake upgrade response.
     *
     * @param string $acceptKey
     * @return ResponseInterface
     */
    private function prepareUpgradeResponse(string $acceptKey): ResponseInterface
    {
        $response = (new Response(101))
            ->withAddedHeader('Upgrade', 'websocket')
            ->withAddedHeader('Connection', 'Upgrade')
            ->withAddedHeader('Sec-WebSocket-Accept', $acceptKey);

        if ($this->connection->isCompressionEnabled()) {
            $compression = $this->connection->getCompression();
            $response = $response->withAddedHeader(
                'Sec-WebSocket-Extensions',
                $compression->compressionConf->getConfAsStringHeader()
            );
        }

        $this->connection->completeHandshake();
        $this->connection->getEventDispatcher()->dispatch(new OnNewConnectionOpenEvent($this->connection));

        return $response;
    }

    /**
     * Handles WebSocket exceptions and logs them appropriately.
     *
     * @param WebSocketException $exception
     * @return void
     */
    private function handleWebSocketException(WebSocketException $exception): void
    {
        $this->connection->getSocketWriter()->close(CloseFrameEnum::CLOSE_NORMAL);
        $this->connection->getLogger()->notice('WebSocket error: ' . $exception->getMessage());
        $this->connection->getEventDispatcher()->dispatch(new OnWebSocketExceptionEvent(
            $exception,
            $this->connection
        ));
    }

    /**
     * Sends the handshake response to the client.
     *
     * @param ResponseInterface $response
     * @return void
     */
    private function sendHandshakeResponse(ResponseInterface $response): void
    {
        $rawResponse = $this->handshakeResponder->prepareHandshakeResponse($response->getHeaderLine("Sec-WebSocket-Accept"));
        if ($this->connection->isCompressionEnabled()) {
            $compression = $this->connection->getCompression();
            $rawResponse = $compression->attachToStringResponse($rawResponse);
        }
        
        $this->connection->getSocketWriter()->write($rawResponse);
    }

    /**
     * Sends an error response back to the client.
     *
     * @param string $errorMessage
     * @return void
     */
    private function sendErrorResponse(string $errorMessage): void
    {
        $response = "HTTP/1.1 400 Bad Request\r\nContent-Length: " .
            strlen($errorMessage) . "\r\nConnection: close\r\n\r\n" .
            json_encode(['error' => $errorMessage]);

        $this->connection->getSocketWriter()->write($response);
        $this->connection->getLogger()->error($errorMessage);
    }
}
