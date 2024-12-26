<?php

declare(strict_types=1);

namespace Brash\Websocket\Compression;

final readonly class CompressionConf
{
    public const int DEFAULT_WINDOW_SIZE = 15;
    public const array DEFAULT_OPTIONS = [
        'client_max_window_bits' => self::DEFAULT_WINDOW_SIZE,
        'client_no_context_takeover' => true,
        'server_max_window_bits' => self::DEFAULT_WINDOW_SIZE,
        'server_no_context_takeover' => true
    ];

    public function __construct(
        public int $serverWindowSize = self::DEFAULT_WINDOW_SIZE,
        public int $clientWindowSize = self::DEFAULT_WINDOW_SIZE,
        public bool $serverContextTakeover = true,
        public bool $clientContextTakeover = true
    ) {
    }

    /**
     * @param array{client_max_window_bits: int, client_no_context_takeover: int, server_max_window_bits: bool, server_no_context_takeover: bool}
     */
    public static function fromArray(array $configs): static
    {
        [
            'client_max_window_bits' => $clientWindowSize,
            'client_no_context_takeover' => $clientContextTakeover,
            'server_max_window_bits' => $serverWindowSize,
            'server_no_context_takeover' => $serverContextTakeover
        ] = array_merge(self::DEFAULT_OPTIONS, $configs);
        $clientWindowSize = $clientWindowSize ? intval($clientWindowSize) : self::DEFAULT_WINDOW_SIZE;
        $serverWindowSize = $serverWindowSize ? intval($serverWindowSize) : self::DEFAULT_WINDOW_SIZE;

        return new self(
            $serverWindowSize,
            $clientWindowSize,
            $serverContextTakeover,
            $clientContextTakeover
        );
    }

    public function getConfAsStringHeader(): string
    {
        $header = 'permessage-deflate';
        if ($this->clientWindowSize !== self::DEFAULT_WINDOW_SIZE) {
            $header .= "; client_max_window_bits={$this->clientWindowSize}";
        }
        if (!$this->clientContextTakeover) {
            $header .= '; client_no_context_takeover';
        }
        if ($this->serverWindowSize !== self::DEFAULT_WINDOW_SIZE) {
            $header .= "; server_max_window_bits={$this->serverWindowSize}";
        }
        if (!$this->serverContextTakeover) {
            $header .= '; server_no_context_takeover';
        }

        return $header;
    }
}
