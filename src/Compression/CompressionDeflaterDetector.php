<?php

namespace Brash\Websocket\Compression;

use Brash\Websocket\Compression\Exceptions\CompressionErrorsCollectionException;
use Brash\Websocket\Compression\Exceptions\InvalidTakeoverException;
use Brash\Websocket\Compression\Exceptions\InvalidWindowSizeException;
use Psr\Http\Message\RequestInterface;

final class CompressionDeflaterDetector
{
    private array $errors = [];

    public function detect(RequestInterface $requestInterface): ?ServerCompressionContext
    {
        $secWebsocketExtensions = $requestInterface->getHeaderLine('Sec-WebSocket-Extensions') ?? "";
        $headerIn = strtolower(trim($secWebsocketExtensions));
        if ($headerIn === '' || !str_starts_with($headerIn, 'permessage-deflate')) {
            return null;
        }

        $parsedHeaders = [];
        foreach (explode(';', substr($headerIn, strlen('permessage-deflate'))) as $part) {
            [$key, $value] = array_map('trim', array_pad(explode('=', $part, 2), 2, ""));
            $parsedHeaders[$key] = $value === "" ? null : $value;
        }

        foreach ($parsedHeaders as $key => $value) {
            match ($key) {
                'server_max_window_bits', 'client_max_window_bits' => $this->validateWindowSize($value, $key),
                'client_no_context_takeover', 'server_no_context_takeover' => $this->validateTakeover($value, $key),
                default => null,
            };
        }

        if ($this->errors) {
            throw new CompressionErrorsCollectionException($this->errors);
        }

        $config = CompressionConf::fromArray($parsedHeaders);

        return new ServerCompressionContext($config);
    }

    private function validateWindowSize(mixed $value, string $field): void
    {
        $validRange = $value > 8 && $value <= 15;
        if ($value === null) {
            return;
        }
        if (!(is_numeric($value) && $validRange)) {
            $this->errors[] = new InvalidWindowSizeException($field, $value);
        }
    }

    private function validateTakeover(mixed $value, string $field): void
    {
        if ($value !== null) {
            $this->errors[] = new InvalidTakeoverException($field);
        }
    }
}
