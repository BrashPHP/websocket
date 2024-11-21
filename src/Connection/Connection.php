<?php

declare(strict_types=1);

namespace Kit\Websocket\Connection;

use Kit\Websocket\Compression\CompressionDeflaterDetector;
use Kit\Websocket\Compression\ServerCompressionContext;
use Kit\Websocket\Connection\Exceptions\FailedWriteException;
use Kit\Websocket\Events\OnDataReceivedEvent;
use Kit\Websocket\Events\OnDisconnectEvent;
use Kit\Websocket\Events\OnNewConnectionOpenEvent;
use Kit\Websocket\Events\OnUpgradeEvent;
use Kit\Websocket\Events\OnWebSocketExceptionEvent;
use Kit\Websocket\Exceptions\WebSocketException;
use Kit\Websocket\Frame\Enums\CloseFrameEnum;
use Kit\Websocket\Frame\Enums\FrameTypeEnum;
use Kit\Websocket\Frame\Frame;
use Kit\Websocket\Http\RequestFactory;
use Kit\Websocket\Message\MessageWriter;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;


class Connection
{
    const int DEFAULT_TIMEOUT = 5; // in seconds
    private bool $handshakeDone = false;
    private ?ServerCompressionContext $compression = null;

    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly MessageWriter $messageWriter,
        private readonly string $ip,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {
    }

    public function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->eventDispatcher;
    }

    public function getIp(): string
    {
        return $this->ip;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    public function getSocketWriter(): MessageWriter
    {
        return $this->messageWriter;
    }

    public function onMessage(string $data): void
    {
        !$this->handshakeDone ?
            $this->handshake($data) :
            $this->processMessage($data);
    }

    public function onEnd()
    {
        if ($this->handshakeDone) {
            $this->eventDispatcher->dispatch(new OnDisconnectEvent($this));

            $this->logger->info(message: "Client disconnected");
        } else {
            $this->logger->info('Disconnected but websocket didn\'t start.');
        }
    }

    public function close(CloseFrameEnum $closeType, ?string $reason = null)
    {
        $this->messageWriter->close($closeType, $reason);
    }

    public function write(string|Frame $frame, FrameTypeEnum $frameTypeEnum): void
    {
        try {
            if ($this->isCompressionEnabled()) {
                $frame = $this->compression->deflateFrame($frame);
            }
            $this->messageWriter->writeFrame(frame: $frame, opCode: $frameTypeEnum);
        } catch (\Throwable $th) {
            $this->logger->error($th);
            if ($th instanceof WebSocketException) {
                throw new FailedWriteException($th);
            }

            throw $th;
        }
    }

    public function isCompressionEnabled()
    {
        return $this->compression !== null;
    }

    public function getCompression(): ?ServerCompressionContext
    {
        return $this->compression;
    }

    public function writeText(string|Frame $frame)
    {
        $this->write($frame, FrameTypeEnum::Text);
    }

    public function onError($data)
    {
        $message = "A connectivity error occurred: $data";
        $this->logger->error($message);
        $this->eventDispatcher->dispatch(new OnWebSocketExceptionEvent(
            new WebSocketException($message),
            $this
        ));
    }

    public function timeout(): void
    {
        $this->logger->notice("Connection to {$this->ip} timed out.");
        $this->messageWriter->close(CloseFrameEnum::CLOSE_PROTOCOL_ERROR);
    }

    public function isHandshakeDone(): bool
    {
        return $this->handshakeDone;
    }

    protected function handshake(string $data): void
    {
        $request = $this->tryCreateRequest($data);
        if ($request) {
            $this->eventDispatcher->dispatch(new OnUpgradeEvent(
                $request
            ))
                ->then(onFulfilled: function (string $handshakeResponse): void {
                    if ($this->isCompressionEnabled()) {
                        $handshakeResponse = $this->compression->attachToStringResponse($handshakeResponse);
                    }
                    $this->logger->debug("Handshake: $handshakeResponse");
                    $this->messageWriter->write($handshakeResponse);
                    $this->handshakeDone = true;
                    $this->eventDispatcher->dispatch(new OnNewConnectionOpenEvent($this));
                })
                ->catch(onRejected: function (WebSocketException $webSocketException): void {
                    $this->messageWriter->close(CloseFrameEnum::CLOSE_NORMAL);
                    $this->logger->notice('Connection to ' . $this->ip . ' closed with error : ' . $webSocketException->getMessage());
                    $this->eventDispatcher->dispatch(new OnWebSocketExceptionEvent($webSocketException, $this));
                });
        }
    }

    protected function processMessage(string $data): void
    {
        $this->logger->debug("Received data", ['data' => $data]);
        $this->eventDispatcher->dispatch(new OnDataReceivedEvent($data, $this));
    }

    private function tryCreateRequest(string $data): ?RequestInterface
    {
        try {
            $request = RequestFactory::createRequest($data);
            $compressionDetector = new CompressionDeflaterDetector();
            $this->compression = $compressionDetector->detect($request);

            return $request;
        } catch (\Throwable $th) {
            $errorMessage = json_encode(['error' => $th->getMessage()]);

            $this->messageWriter->write("HTTP/1.1 400 OK\r\nContent-Length: " .
            strlen($errorMessage) . "\r\nConnection: close\r\n\r\n" .
                $errorMessage);

            $this->logger->error(dump($th->getMessage()));

            return null;
        }
    }
}

