<?php

declare(strict_types=1);

namespace Brash\Websocket\Message;

use Generator;
use Brash\Websocket\Frame\Enums\CloseFrameEnum;
use Brash\Websocket\Frame\FrameFactory;
use Brash\Websocket\Message\Message;
use Brash\Websocket\Message\Orchestration\MessageOrchestrator;

class MessageProcessor
{
    private readonly MessageOrchestrator $messageOrchestrator;

    public function __construct(
        private readonly MessageFactory $messageFactory,
        private readonly FrameFactory $frameFactory
    ) {
        $this->messageOrchestrator = new MessageOrchestrator($frameFactory);
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
                        // $this->messageWriter->writeExceptionCode($response->getCloseType()); JUST IN CASE
                        yield $this->closeMessage($response->getCloseType());

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

            } catch (\Throwable $th) {
                dump($th);
                yield $this->closeMessage(CloseFrameEnum::CLOSE_UNEXPECTING_CONDITION);

                $messageBus->setData(null);
            }
        } while ($messageBus->hasValidData());
    }

    private function closeMessage(CloseFrameEnum $closeCode): Message
    {
        $message = $this->messageFactory->createMessage();
        $closeFrame = $this->frameFactory->createCloseFrame(status: $closeCode);
        $message->addFrame($closeFrame);

        return $message;
    }
}
