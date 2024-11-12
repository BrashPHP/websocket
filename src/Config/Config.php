<?php


namespace Kit\Websocket\Config;

use Kit\Websocket\Config\Exceptions\XdebugEnabledException;

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
        public bool $writeMasked = false,
        public bool $prod = true,
        public ?SslConfig $sslConfig = null
    ) {
        if ($this->prod && \extension_loaded('xdebug')) {
            throw new XdebugEnabledException();
        }
    }

    public static function createFromArray(array $config): static
    {
        $config = \array_merge([
            'timeout' => self::MESSAGE_TIMEOUT_IN_SECONDS,
            'maxPayloadSize' => self::MAX_PAYLOAD_SIZE,
            'maxMessagesBuffering' => self::MAX_MESSAGES_BUFFERING,
            'writeMasked' => false,
            'prod' => true,
            'ssl' => false,
            'certFile' => '',
            'passphrase' => '',
            'sslContextOptions' => [],
        ], $config);

        // Assign the element at index 'baz' to the variable $three
        [
            'timeout' => $timeout,
            'maxPayloadSize' => $maxPayloadSize,
            'maxMessagesBuffering' => $maxMessagesBuffering,
            'writeMasked' => $writeMasked,
            'prod' => $prod,
            'ssl' => $ssl,
            'certFile' => $certFile,
            'passphrase' => $passphrase,
            'sslContextOptions' => $sslContextOptions,
        ] = $config;


        $sslConfig = $ssl ? new SslConfig(
            $certFile,
            $passphrase,
            $sslContextOptions
        ) : null;

        return new Config(
            timeout: $timeout,
            maxPayloadSize: $maxPayloadSize,
            maxMessagesBuffering: $maxMessagesBuffering,
            writeMasked: $writeMasked,
            prod: $prod,
            sslConfig: $sslConfig
        );
    }
}
