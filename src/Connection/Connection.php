<?php

declare(strict_types=1);

namespace Kit\Websocket\Connection;

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
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;


class Connection
{
    const int DEFAULT_TIMEOUT = 5; // in seconds
    private bool $handshakeDone = false;

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
        if (!$this->handshakeDone) {
            $this->logger->info('Disconnected but websocket didn\'t start.');
            return;
        }

        $this->eventDispatcher->dispatch(new OnDisconnectEvent($this));

        $this->logger->info(message: "Client disconnected");
    }

    public function close(CloseFrameEnum $closeType, ?string $reason = null)
    {
        $this->messageWriter->close($closeType, $reason);
    }

    public function write(string|Frame $frame, FrameTypeEnum $frameTypeEnum): void
    {
        try {
            $this->messageWriter->writeFrame(frame: $frame, opCode: $frameTypeEnum);
        } catch (\Throwable $th) {
            if ($th instanceof WebSocketException) {
                throw new FailedWriteException($th);
            }

            throw $th;
        }
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

    protected function handshake(string $data): void
    {
        try {
            $request = RequestFactory::createRequest($data);
        } catch (\Throwable $th) {
            $errorMessage = "<html><h1>Bad Request!</h1><pre>{$th->getMessage()}</pre></html>\r\n";

            $this->messageWriter->write("HTTP/1.1 400 OK\r\nContent-Length: " .
                strlen($errorMessage) . "\r\nConnection: close\r\n\r\n" .
                $errorMessage);

            return;
        }
        $this->eventDispatcher->dispatch(new OnUpgradeEvent(
            $request
        ))
            ->then(onFulfilled: function (string $handshakeResponse): void {
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

    protected function processMessage(string $data): void
    {
        $this->logger->debug("Received data", ['data' => $data]);
        $this->eventDispatcher->dispatch(new OnDataReceivedEvent($data, $this));
    }

    public function timeout()
    {
        $this->logger->notice("Connection to {$this->ip} timed out.");
        $this->messageWriter->close(CloseFrameEnum::CLOSE_PROTOCOL_ERROR);
    }

    public function isHandshakeDone()
    {
        return $this->handshakeDone;
    }
}

