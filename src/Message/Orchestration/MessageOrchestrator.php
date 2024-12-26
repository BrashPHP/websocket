<?php

declare(strict_types=1);

namespace Brash\Websocket\Message\Orchestration;

use Brash\Websocket\Frame\FrameFactory;
use Brash\Websocket\Message\Message;
use Brash\Websocket\Message\MessageBus;
use Brash\Websocket\Message\Validation\AbstractMessageValidator;
use Brash\Websocket\Message\Validation\CanIncludeFrame;
use Brash\Websocket\Message\Validation\ValidateFrame;
use Brash\Websocket\Message\Validation\ValidateOpCode;

use function Brash\Websocket\functions\removeStart;

final class MessageOrchestrator
{
    private readonly AbstractMessageValidator $messageValidator;
    private string $buffer;

    public function __construct(private readonly FrameFactory $frameFactory)
    {
        $this->buffer = '';
        $this->messageValidator = new ValidateOpCode();
        $this
            ->messageValidator
            ->setNext(new ValidateFrame())
            ->setNext(new CanIncludeFrame());
    }

    /**
     * Orchestrates message so they can be managed, validated and directed.
     */
    public function conduct(MessageBus $messageBus, Message $message): OrchestrationResponse
    {
        $this->addBuffer($messageBus->getData());
        $exception = null;

        while (
            $messageBus->hasValidData() &&
            !$message->isComplete() &&
            is_null($exception)
        ) {
            $frameOrFail = $this->frameFactory->newFrameFromRawData($this->getBuffer());

            if ($frameOrFail instanceof \Exception) {
                $exception = $frameOrFail;

                continue;
            }

            $frame = $frameOrFail;

            $result = $this->messageValidator->validate($message, $frame);

            if ($result->success()) {
                $message = $result->successfulMessage;

                $data = $this->removeFromBuffer($frame->getRawData());

                $messageBus->setData($data);

                continue;
            }

            $exception = $result->error;
        }

        $this->clearBuffer();

        return new OrchestrationResponse(successfullMessage: $message, failedResponse: $exception);
    }

    private function addBuffer($data)
    {
        $this->buffer .= $data;
    }

    private function clearBuffer()
    {
        $this->buffer = '';
    }

    private function getBuffer(): string
    {
        return $this->buffer;
    }

    private function removeFromBuffer(string $rawData): string
    {
        $this->buffer = removeStart($this->buffer, $rawData);

        return $this->buffer;
    }
}
