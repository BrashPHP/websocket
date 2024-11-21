<?php

declare(strict_types=1);

namespace Kit\Websocket\Compression;

use Kit\Websocket\Compression\CompressionConf;
use Kit\Websocket\Compression\Exceptions\BadCompressionException;

final readonly class Rfc7692Compression
{
    public const int DEFAULT_WINDOW_SIZE = 15;
    private const int RSV = 0b100;
    private const int MINIMUM_LENGTH = 860;
    private const string EMPTY_BLOCK = "\x0\x0\xff\xff";

    private \DeflateContext $deflate;
    private \InflateContext $inflate;
    private int $sendingFlushMode;
    private int $receivingFlushMode;

    public static function createRequestHeader(): string
    {
        return 'permessage-deflate; server_no_context_takeover; client_no_context_takeover';
    }

    public function __construct(
        bool $isServer,
        CompressionConf $settings
    ) {
        $receivingWindowSize = $isServer ? $settings->clientWindowSize : $settings->serverWindowSize;
        $sendingWindowSize = $isServer ? $settings->serverWindowSize : $settings->clientWindowSize;
        $receivingContextTakeover = $settings->clientContextTakeover;
        $sendingContextTakeover = $settings->serverContextTakeover;

        $this->receivingFlushMode = $receivingContextTakeover ? \ZLIB_SYNC_FLUSH : \ZLIB_FULL_FLUSH;
        $this->sendingFlushMode = $sendingContextTakeover ? \ZLIB_SYNC_FLUSH : \ZLIB_FULL_FLUSH;

        $this->initializeContexts($receivingWindowSize, $sendingWindowSize);
    }

    private function initializeContexts(int $receivingWindowSize, int $sendingWindowSize): void
    {
        $this->inflate = \inflate_init(\ZLIB_ENCODING_RAW, ['window' => $receivingWindowSize]);
        $this->deflate = \deflate_init(\ZLIB_ENCODING_RAW, ['window' => $sendingWindowSize]);
    }

    public function getRsv(): int
    {
        return self::RSV;
    }

    public function getCompressionThreshold(): int
    {
        return self::MINIMUM_LENGTH;
    }

    public function decompress(string $data, bool $isFinal): ?string
    {
        if ($isFinal) {
            $data .= self::EMPTY_BLOCK;
        }

        try {
            $data = \inflate_add($this->inflate, $data, $this->receivingFlushMode);
        } catch (\Throwable) {
            return null;
        }

        return $data ?: null;
    }

    public function compress(string $data, bool $isFinal): string
    {
        try {
            $data = \deflate_add($this->deflate, $data, $this->sendingFlushMode);
        } catch (\Throwable $th) {
            throw new BadCompressionException($data, $th);
        }

        return $isFinal && \substr($data, -4) === self::EMPTY_BLOCK ? \substr($data, 0, -4) : $data;
    }
}
