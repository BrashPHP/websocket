<?php

declare(strict_types=1);

namespace Kit\Websocket\Message;

use Kit\Websocket\Exceptions\IncoherentDataException;
use Kit\Websocket\Frame\Enums\CloseFrameEnum;
use Kit\Websocket\Frame\Exceptions\ProtocolErrorException;
use Kit\Websocket\Frame\Frame;
use Kit\Websocket\Frame\FrameFactory;
use Kit\Websocket\Message\Exceptions\LimitationException;
use Kit\Websocket\Message\Validation\AbstractMessageValidator;
use Kit\Websocket\Message\Validation\CanIncludeFrame;
use Kit\Websocket\Message\Validation\ValidateFrame;
use Kit\Websocket\Message\Validation\ValidateOpCode;
use function Kit\Websocket\functions\removeStart;

final class MessageOrchestrator
{
    private ?\Exception $preparedException;
    private AbstractMessageValidator $messageValidator;
    private string $buffer;

    public function __construct(private FrameFactory $frameFactory)
    {
        $this->preparedException = null;
        $this->buffer = '';
        $this->messageValidator = new ValidateOpCode();
        $this->messageValidator
            ->setNext(new ValidateFrame())
            ->setNext(new CanIncludeFrame());
    }

    public function onData(MessageBus $messageBus, Message $message): Message|null
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

        $this->preparedException = $exception;

        $this->clearBuffer();

        return $message;
    }

    public function failed(): bool
    {
        return !is_null($this->preparedException);
    }

    public function getCloseType(): CloseFrameEnum
    {
        return match (get_class($this->preparedException)) {
            IncoherentDataException::class => CloseFrameEnum::CLOSE_INCOHERENT_DATA,
            ProtocolErrorException::class => CloseFrameEnum::CLOSE_PROTOCOL_ERROR,
            LimitationException::class => CloseFrameEnum::CLOSE_TOO_BIG_TO_PROCESS,
            default => CloseFrameEnum::CLOSE_UNEXPECTING_CONDITION,
        };
    }

    public function addBuffer($data)
    {
        $this->buffer .= $data;
    }

    public function clearBuffer()
    {
        $this->buffer = '';
    }

    public function getBuffer(): string
    {
        return $this->buffer;
    }

    public function removeFromBuffer(string $rawData): string
    {
        $this->buffer = removeStart($this->buffer, $rawData);

        return $this->buffer;
    }

    public function getError(): \Exception
    {
        return $this->preparedException;
    }
}
