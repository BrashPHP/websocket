<?php

declare(strict_types=1);

namespace Brash\Websocket\Connection;

use Brash\Websocket\Compression\ServerCompressionContext;
use Brash\Websocket\Connection\Exceptions\FailedWriteException;
use Brash\Websocket\Events\OnDataReceivedEvent;
use Brash\Websocket\Events\OnDisconnectEvent;
use Brash\Websocket\Events\OnWebSocketExceptionEvent;
use Brash\Websocket\Exceptions\WebSocketException;
use Brash\Websocket\Frame\Enums\CloseFrameEnum;
use Brash\Websocket\Frame\Enums\FrameTypeEnum;
use Brash\Websocket\Frame\Frame;
use Brash\Websocket\Message\MessageWriter;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;


class Connection
{
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

    public function onEnd(): void
    {
        if ($this->handshakeDone) {
            $this->eventDispatcher->dispatch(new OnDisconnectEvent($this));

            $this->logger->info(message: "Client disconnected");
        } else {
            $this->logger->info('Disconnected but websocket didn\'t start.');
        }
    }

    public function close(CloseFrameEnum $closeType, ?string $reason = null): void
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

    public function isCompressionEnabled(): bool
    {
        return $this->compression !== null;
    }

    public function getCompression(): ?ServerCompressionContext
    {
        return $this->compression;
    }

    public function setCompression(?ServerCompressionContext $serverCompressionContext): void{
        $this->compression = $serverCompressionContext;
    }

    public function completeHandshake(){
        $this->handshakeDone = true;
    }

    public function writeText(string|Frame $frame): void
    {
        $this->write($frame, FrameTypeEnum::Text);
    }

    public function writeBinary(string|Frame $frame): void
    {
        $this->write($frame, FrameTypeEnum::Binary);
    }

    public function onError($data): void
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

    protected function processMessage(string $data): void
    {
        $this->eventDispatcher->dispatch(new OnDataReceivedEvent($data, $this));
    }

    protected function handshake(string $data): void{
        $handshaker = new ConnectionHandshake($this);

        $handshaker->handshake($data);
    }
}

