<?php

namespace Kit\Websocket\Message\Orchestration;
use Kit\Websocket\Exceptions\IncoherentDataException;
use Kit\Websocket\Frame\Enums\CloseFrameEnum;
use Kit\Websocket\Frame\Exceptions\IncompleteFrameException;
use Kit\Websocket\Frame\Exceptions\ProtocolErrorException;
use Kit\Websocket\Message\Exceptions\LimitationException;
use Kit\Websocket\Message\Message;

final readonly class OrchestrationResponse
{
    public function __construct(
        public ?Message $successfullMessage = null,
        public ?\Exception $failedResponse = null
    ) {
    }

    public function failed(): bool
    {
        return !is_null($this->failedResponse);
    }

    public function isIncompleteException(): bool
    {
        return $this->failedResponse instanceof IncompleteFrameException;
    }

    public function getCloseType(): CloseFrameEnum
    {
        return match (get_class($this->failedResponse)) {
            IncoherentDataException::class => CloseFrameEnum::CLOSE_INCOHERENT_DATA,
            ProtocolErrorException::class => CloseFrameEnum::CLOSE_PROTOCOL_ERROR,
            LimitationException::class => CloseFrameEnum::CLOSE_TOO_BIG_TO_PROCESS,
            default => CloseFrameEnum::CLOSE_UNEXPECTING_CONDITION,
        };
    }
}


