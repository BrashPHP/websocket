<?php

declare(strict_types=1);

namespace Kit\Websocket\Message;

use Kit\Websocket\Frame\Enums\CloseFrameEnum;
use Kit\Websocket\Frame\Enums\FrameTypeEnum;
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
    public function process(string $data, ConnectionInterface $socket, Message $message): void
    {
        $messageOrchestrator = new MessageOrchestrator($this->frameFactory);
        do {
            try {
                $message ??= $this->createMessage();
                $message = $messageOrchestrator->onData($data, $message);

                if (is_null($message)) {
                    if ($messageOrchestrator->failed()) {
                        $this->onException($messageOrchestrator->getCloseType(), $socket);
                        break;
                    }

                    continue;
                }

                $this->processHelper($message, $socket);
            } catch (\Throwable $th) {
                $this->onException(CloseFrameEnum::CLOSE_UNEXPECTING_CONDITION, $socket);
            }
        } while (!empty($data));
    }

    public function createMessage()
    {
        return new Message($this->maxMessagesBuffering);
    }


    public function write(Frame|string $frame, ConnectionInterface $socket, FrameTypeEnum $opCode = FrameTypeEnum::Text): void
    {
        if (!$frame instanceof Frame) {
            $frame = $this->frameFactory->newFrame(
                payload: $frame,
                frameTypeEnum: $opCode,
                writeMask: $this->writeMasked
            );
        }

        $socket->write($frame->getRawData());
    }


    public function getFrameFactory(): FrameFactory
    {
        return $this->frameFactory;
    }

    public function addHandler(FrameHandlerInterface $handler): static
    {
        $this->handlers[] = $handler;

        return $this;
    }

    public function timeout(ConnectionInterface $socket)
    {
        $this->write($this->frameFactory->createCloseFrame(CloseFrameEnum::CLOSE_PROTOCOL_ERROR), $socket);
        $socket->close();
    }

    public function close(
        ConnectionInterface $socket,
        CloseFrameEnum $status = CloseFrameEnum::CLOSE_NORMAL,
        string $reason = null
    ): void {
        $this->write($this->frameFactory->createCloseFrame($status, $reason), $socket);

        $socket->end();
    }

    private function processHelper(Message $message, ConnectionInterface $socket): void
    {
        foreach ($this->handlers as $handler) {
            if ($handler->supports(message: $message)) {
                $handler->process(message: $message, messageProcessor: $this, socket: $socket);
            }
        }
    }

    private function onException(CloseFrameEnum $closeCode, ConnectionInterface $socket)
    {
        $this->write($this->frameFactory->createCloseFrame(status: $closeCode), $socket);
        $socket->end();
    }
}
