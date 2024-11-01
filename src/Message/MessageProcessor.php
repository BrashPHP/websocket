<?php

declare(strict_types=1);

namespace Kit\Websocket\Message;

use Generator;
use Kit\Websocket\Frame\Enums\CloseFrameEnum;
use Kit\Websocket\Frame\Enums\FrameTypeEnum;
use Kit\Websocket\Frame\Frame;
use Kit\Websocket\Frame\FrameFactory;
use Kit\Websocket\Frame\Protocols\FrameHandlerInterface;
use Kit\Websocket\Message\Message;
use Kit\Websocket\Message\Orchestration\MessageOrchestrator;
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
    private MessageOrchestrator $messageOrchestrator;

    public function __construct(
        private FrameFactory $frameFactory,
        private ConnectionInterface $socket,
        private bool $writeMasked = false,
        private ?int $maxMessagesBuffering = null
    ) {
        $this->messageOrchestrator = new MessageOrchestrator($this->frameFactory);
        $this->writeMasked = $writeMasked;
        $this->handlers = [];
    }

    /**
     * Process socket data to generate and handle a `Message` entity.
     * Handles ws-frames, bin-frames, and control frames with buffering logic.
     *
     * Always returns a Message, whether complete or incomplete.
     *
     * @return Generator<Message>
     */
    public function process(string $data, $unfinishedMessage = null): Generator
    {
        $messageBus = new MessageBus($data);
        $message = $unfinishedMessage;
        do {
            try {
                $message ??= $this->createMessage();
                $response = $this->messageOrchestrator->conduct($messageBus, $message);

                if ($response->failed()) {
                    $messageBus->setData('');

                    if (!$response->isIncompleteException()) {
                        $this->onException($response->getCloseType());

                        break;
                    }
                }

                if ($response->successfullMessage->isContinuationMessage()) {
                    yield $response->successfullMessage;

                    continue;
                }

                $message = $response->successfullMessage;

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

    public function timeout(): void
    {
        $this->write($this->frameFactory->createCloseFrame(CloseFrameEnum::CLOSE_PROTOCOL_ERROR));
        $this->socket->close();
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
