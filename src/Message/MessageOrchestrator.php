<?php

namespace Kit\Websocket\Message;

declare(strict_types=1);

use Kit\Websocket\Exceptions\IncoherentDataException;
use Kit\Websocket\Frame\Enums\CloseFrameEnum;
use Kit\Websocket\Frame\Enums\FrameTypeEnum;
use Kit\Websocket\Frame\Exceptions\IncompleteFrameException;
use Kit\Websocket\Frame\Exceptions\ProtocolErrorException;
use Kit\Websocket\Frame\Frame;
use Kit\Websocket\Frame\FrameFactory;
use Kit\Websocket\Frame\FrameValidation\ValidationUponOpCode;
use Kit\Websocket\Message\Exceptions\LimitationException;
use Kit\Websocket\Message\Validation\AbstractMessageValidator;
use Kit\Websocket\Message\Validation\CanIncludeFrame;
use Kit\Websocket\Message\Validation\ValidateFrame;
use Kit\Websocket\Message\Validation\ValidateOpCode;

final class MessageOrchestrator
{
    private ?\Exception $preparedException;

    private AbstractMessageValidator $messageValidator;

    public function __construct(private FrameFactory $frameFactory)
    {
        $this->preparedException = null;
        $this->messageValidator = new ValidateOpCode(new ValidationUponOpCode());
        $this->messageValidator->setNext(new ValidateFrame())->setNext(new CanIncludeFrame());
    }

    public function onData(string &$data, Message $message): Message|null
    {
        $message->addBuffer($data);
        $incompleteMessage = fn(): bool => !$message->isComplete();
        $noExceptionThrown = fn(): bool => is_null($this->preparedException);

        $canContinueCondition = fn(): bool => $data && $incompleteMessage() && $noExceptionThrown();

        while ($canContinueCondition()) {
            $frame = $this->frameFactory->newFrameFromRawData($message->getBuffer());

            if ($frame instanceof IncompleteFrameException) {
                break;
            }

            $result = $this->messageValidator->validate($message, $frame);

            if (!$result->success()) {
                $this->preparedException = $result->error;
            }

            $message = $result->successfulMessage;

            if ($message->isContinuationMessage()) {
                return $message;
            }

            $data = $message->removeFromBuffer($frame);
        }

        return $message->isComplete() ? $message : null;
    }

    public function failed()
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
}
