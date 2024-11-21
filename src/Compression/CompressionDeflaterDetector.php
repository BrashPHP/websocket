<?php

namespace Kit\Websocket\Compression;

use Kit\Websocket\Compression\Exceptions\CompressionErrorsCollectionException;
use Kit\Websocket\Compression\Exceptions\InvalidTakeoverException;
use Kit\Websocket\Compression\Exceptions\InvalidWindowSizeException;
use Psr\Http\Message\RequestInterface;

final class CompressionDeflaterDetector
{
    private array $errors = [];

    public function detect(RequestInterface $requestInterface): ?ServerCompressionContext
    {
        $headerIn = strtolower(trim($requestInterface->getHeaderLine('Sec-WebSocket-Extensions')));
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
        if (!is_int($value) || $value < 9 || $value > 15) {
            $this->errors[] = new InvalidWindowSizeException($field);
        }
    }

    private function validateTakeover(mixed $value, string $field): void
    {
        if ($value !== null) {
            $this->errors[] = new InvalidTakeoverException($field);
        }
    }
}
