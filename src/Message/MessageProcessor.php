<?php

declare(strict_types=1);

namespace Kit\Websocket\Message;

use Generator;
use Kit\Websocket\Frame\Enums\CloseFrameEnum;
use Kit\Websocket\Frame\Protocols\FrameHandlerInterface;
use Kit\Websocket\Message\Message;
use Kit\Websocket\Message\Orchestration\MessageOrchestrator;

class MessageProcessor
{
    /** @var FrameHandlerInterface[] */
    private array $handlers;
    private readonly MessageOrchestrator $messageOrchestrator;

    public function __construct(
        private readonly MessageWriter $messageWriter,
        private readonly MessageFactory $messageFactory
    ) {
        $this->messageOrchestrator = new MessageOrchestrator($messageWriter->getFrameFactory());
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
                $message ??= $this->messageFactory->createMessage();
                $response = $this->messageOrchestrator->conduct($messageBus, $message);

                if ($response->failed()) {
                    $messageBus->setData('');

                    if (!$response->isIncompleteException()) {
                        $this->messageWriter->writeExceptionCode($response->getCloseType());

                        break;
                    }
                }

                if ($response->successfullMessage->isContinuationMessage()) {
                    yield $response->successfullMessage;

                    continue;
                }

                $message = $response->successfullMessage;

                if ($message->isComplete()) {
                    yield $message;

                    $message = null;
                } else {
                    yield $message;
                }

            } catch (\Throwable) {
                $this->messageWriter->writeExceptionCode(CloseFrameEnum::CLOSE_UNEXPECTING_CONDITION);

                $messageBus->setData(null);
            }
        } while ($messageBus->hasValidData());
    }

    // public function addHandler(FrameHandlerInterface $handler): static
    // {
    //     $this->handlers[] = $handler;

    //     return $this;
    // }

    // private function processHelper(Message $message): void
    // {
    //     foreach ($this->handlers as $handler) {
    //         if ($handler->supports(message: $message)) {
    //             $handler->process(message: $message, messageWriter: $this->messageWriter);
    //         }
    //     }
    // }
}
