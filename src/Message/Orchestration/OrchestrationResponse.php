<?php

namespace Brash\Websocket\Message\Orchestration;
use Brash\Websocket\Exceptions\IncoherentDataException;
use Brash\Websocket\Frame\Enums\CloseFrameEnum;
use Brash\Websocket\Frame\Exceptions\IncompleteFrameException;
use Brash\Websocket\Frame\Exceptions\ProtocolErrorException;
use Brash\Websocket\Message\Exceptions\LimitationException;
use Brash\Websocket\Message\Message;

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
        return match ($this->failedResponse !== null ? $this->failedResponse::class : self::class) {
            IncoherentDataException::class => CloseFrameEnum::CLOSE_INCOHERENT_DATA,
            ProtocolErrorException::class => CloseFrameEnum::CLOSE_PROTOCOL_ERROR,
            LimitationException::class => CloseFrameEnum::CLOSE_TOO_BIG_TO_PROCESS,
            default => CloseFrameEnum::CLOSE_UNEXPECTING_CONDITION,
        };
    }
}


