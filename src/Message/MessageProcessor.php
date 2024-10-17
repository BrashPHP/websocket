<?php

declare(strict_types=1);

namespace Kit\Websocket\Message;

use Kit\Websocket\Frame\Enums\CloseFrameEnum;
use Kit\Websocket\Frame\Enums\FrameTypeEnum;
use Kit\Websocket\Frame\Exceptions\IncompleteFrameException;
use Kit\Websocket\Frame\Frame;
use Kit\Websocket\Frame\FrameFactory;
use Kit\Websocket\Frame\Protocols\FrameHandlerInterface;
use Kit\Websocket\Message\Message;
use React\Socket\ConnectionInterface;

/**
 * Class MessageProcessor
 *
 * This class is only a helper for Connection to avoid having so much instances of classes in memory.
 * Using it like that allow us to have only one instance of MessageProcessor.
 */
class MessageProcessor
{

    /**
     * @var FrameHandlerInterface[]
     */
    private array $handlers;

    public function __construct(
        private FrameFactory $frameFactory,
        private ConnectionInterface $socket,
        private bool $writeMasked = false,
        private ?int $maxMessagesBuffering = null
    ) {
        $this->writeMasked = $writeMasked;
        $this->handlers = [];
    }

    /**
     * Process socket data to generate and handle a `Message` entity.
     * Handles ws-frames, bin-frames, and control frames with buffering logic.
     *
     */
    public function process(string $data, $unfinishedMessage = null)
    {
        $messageOrchestrator = new MessageOrchestrator($this->frameFactory);
        $messageBus = new MessageBus($data);
        $message = $unfinishedMessage;
        do {
            try {
                $message ??= $this->createMessage();
                $messageOrchestratorResponse = $messageOrchestrator->onData($messageBus, $message);

                if ($messageOrchestrator->failed()) {
                    $messageBus->setData('');

                    if (!($messageOrchestrator->getError() instanceof IncompleteFrameException)) {
                        $this->onException($messageOrchestrator->getCloseType());

                        break;
                    }
                }

                if ($messageOrchestratorResponse->isContinuationMessage()) {
                    yield $messageOrchestratorResponse;

                    continue;
                }

                if ($message->isComplete()) {
                    $this->processHelper($message);

                    yield $message;

                    $message = null;
                } else {
                    yield $message;
                }

            } catch (\Throwable $th) {
                $this->onException(CloseFrameEnum::CLOSE_UNEXPECTING_CONDITION);

                $messageBus->setData(null);
            }
        } while ($messageBus->hasValidData());
    }

    public function withSocket(ConnectionInterface $connectionInterface): static
    {
        return new self(
            $this->frameFactory,
            $connectionInterface,
            $this->writeMasked,
            $this->maxMessagesBuffering
        );
    }

    public function createMessage()
    {
        return new Message($this->maxMessagesBuffering);
    }


    public function write(Frame|string $frame, FrameTypeEnum $opCode = FrameTypeEnum::Text): void
    {
        if (!$frame instanceof Frame) {
            $frame = $this->frameFactory->newFrame(
                payload: $frame,
                frameTypeEnum: $opCode,
                writeMask: $this->writeMasked
            );
        }

        $this->socket->write($frame->getRawData());
    }


    public function getFrameFactory(): FrameFactory
    {
        return $this->frameFactory;
    }

    public function getProcessConnection(): ConnectionInterface
    {
        return $this->socket;
    }

    public function addHandler(FrameHandlerInterface $handler): static
    {
        $this->handlers[] = $handler;

        return $this;
    }

    public function timeout(ConnectionInterface $socket)
    {
        $this->write($this->frameFactory->createCloseFrame(CloseFrameEnum::CLOSE_PROTOCOL_ERROR));
        $socket->close();
    }

    public function close(
        CloseFrameEnum $status = CloseFrameEnum::CLOSE_NORMAL,
        string $reason = null
    ): void {
        $this->write($this->frameFactory->createCloseFrame($status, $reason));

        $this->socket->end();
    }

    private function processHelper(Message $message): void
    {
        foreach ($this->handlers as $handler) {
            if ($handler->supports(message: $message)) {
                $handler->process(message: $message, messageProcessor: $this, socket: $this->socket);
            }
        }
    }

    private function onException(CloseFrameEnum $closeCode)
    {
        $closeFrame = $this->frameFactory->createCloseFrame(status: $closeCode);
        $this->write($closeFrame);
        $this->socket->end();
    }
}
