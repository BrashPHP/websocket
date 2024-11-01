<?php

declare(strict_types=1);

namespace Kit\Websocket\Connection;

use Kit\Websocket\Connection\Exceptions\FailedWriteException;
use Kit\Websocket\Exceptions\WebSocketException;
use Kit\Websocket\Frame\Enums\CloseFrameEnum;
use Kit\Websocket\Frame\Enums\FrameTypeEnum;
use Kit\Websocket\Frame\Frame;
use Kit\Websocket\Handlers\OnUpgradeHandler;
use Kit\Websocket\Http\RequestFactory;
use Kit\Websocket\Message\MessageProcessor;
use Kit\Websocket\Message\Protocols\MessageHandlerInterface;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use React\Promise\Deferred;
use React\Promise\Promise;
use React\Socket\ConnectionInterface;

class Connection
{
    const int DEFAULT_TIMEOUT = 5; // in seconds

    protected UriInterface $uri;

    public function __construct(
        private MessageHandlerInterface $messageHandlerInterface,
        private MessageProcessor $messageProcessor,
        private ConnectionInterface $socketStream,
        private TimeoutHandler $timeoutHandler,
        private ?LoggerInterface $logger = new NullLogger(),
        private bool $handshakeDone = false,
    ) {
        $this->timeoutHandler->setTimeoutAction(function (): void {
            $this->logger->notice('Connection to ' . $this->getIp() . ' timed out.');
            $this->messageProcessor->timeout();
        });
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

        $this->messageHandlerInterface->onDisconnect(connection: $this)->then(function (): void {
            $this->logger->info(message: "Client disconnected");
        });
    }

    public function getIp(): string
    {
        return $this->socketStream->getRemoteAddress();
    }

    public function close(CloseFrameEnum $closeType, ?string $reason = null)
    {
        $this->messageProcessor->close($closeType, $reason);
    }

    public function write(string|Frame $frame, FrameTypeEnum $frameTypeEnum): Promise
    {
        $promise = new Promise(fn() => $this->messageProcessor->write(frame: $frame, opCode: $frameTypeEnum));
        $promise->catch(function (\Exception $ex): never {
            if ($ex instanceof WebSocketException) {
                throw new FailedWriteException($ex);
            }

            throw $ex;
        });

        return $promise;
    }

    public function onError($error)
    {
        $this->error($error);
    }

    protected function handshake(string $data): void
    {
        $request = RequestFactory::createRequest($data);
        $onUpgradeHandler = new OnUpgradeHandler();
        $onUpgradeHandler->execute($request)
            ->then(onFulfilled: function (string $handshakeResponse): void {
                $this->socketStream->write($handshakeResponse);
                $this->handshakeDone = true;
                $this->messageHandlerInterface->onOpen($this);
            })
            ->catch(onRejected: function (WebSocketException $webSocketException): void {
                $this->messageProcessor->close(CloseFrameEnum::CLOSE_NORMAL);
                $this->logger->notice('Connection to ' . $this->getIp() . ' closed with error : ' . $webSocketException->getMessage());
                $this->messageHandlerInterface->onError($webSocketException, $this);
            });
    }

    protected function processMessage(string $data): void
    {
        $currentMessage = null;
        $notifyTimeout = new Deferred();
        $this->timeoutHandler->handleConnectionTimeout($notifyTimeout->promise());
        
        foreach ($this->messageProcessor->process(data: $data, unfinishedMessage: $currentMessage) as $message) {
            $currentMessage = $message;
            if ($currentMessage->isComplete()) {
                if ($this->messageHandlerInterface->supportsFrame(opcode: $currentMessage->getOpcode())) {
                    $data = $currentMessage->getContent();
                    $this->messageHandlerInterface->handle(data: $data, connection: $this);
                }
                $currentMessage = null;

                continue;
            }
            // We wait for more data so we start a timeout.
            $notifyTimeout->resolve(true);
        }
    }

    protected function error($data)
    {
        $message = "A connectivity error occurred: $data";
        $this->logger->error($message);
        $this->messageHandlerInterface->onError(e: new WebSocketException($message), connection: $this);
    }
}

