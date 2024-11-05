<?php


namespace Kit\Websocket\Connection;

final readonly class Config
{
    public const int MAX_MESSAGES_BUFFERING = 100;
    public const int MESSAGE_TIMEOUT_IN_SECONDS = 5;
    /**
     * (0.5MiB) Adjust if needed.
     */
    public const int MAX_PAYLOAD_SIZE = 524288;
    public function __construct(
        public int $timeout = self::MESSAGE_TIMEOUT_IN_SECONDS,
        public int $maxPayloadSize = self::MAX_PAYLOAD_SIZE,
        public int $maxMessagesBuffering = self::MAX_MESSAGES_BUFFERING,
        public bool $writeMasked = false
    ) {

    }
}
