<?php

/**
 * Copyright (C) 2014-2024 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

namespace Brash\Websocket\Http;

use BadMethodCallException;
use InvalidArgumentException;
use Psr\Http\Message\{
    MessageInterface,
    StreamInterface
};
use Stringable;

/**
 * Phrity\WebSocket\Http\Message class.
 * Only used for handshake procedure.
 */
abstract class HttpMessage implements MessageInterface, Stringable
{
    protected string $version = '1.1';
    protected array $headers = [];

    /**
     * Retrieves the HTTP protocol version as a string.
     * @return string HTTP protocol version.
     */
    #[\Override]
    public function getProtocolVersion(): string
    {
        return $this->version;
    }

    /**
     * Return an instance with the specified HTTP protocol version.
     * @param string $version HTTP protocol version
     * @return static
     */
    #[\Override]
    public function withProtocolVersion(string $version): self
    {
        $new = clone $this;
        $new->version = $version;
        return $new;
    }

    /**
     * Retrieves all message header values.
     * @return string[][] Returns an associative array of the message's headers.
     */
    #[\Override]
    public function getHeaders(): array
    {
        return array_merge(...array_values($this->headers));
    }

    /**
     * Checks if a header exists by the given case-insensitive name.
     * @param string $name Case-insensitive header field name.
     * @return bool Returns true if any header names match the given header.
     */
    #[\Override]
    public function hasHeader(string $name): bool
    {
        return array_key_exists(strtolower($name), $this->headers);
    }

    /**
     * Retrieves a message header value by the given case-insensitive name.
     * @param string $name Case-insensitive header field name.
     * @return string[] An array of string values as provided for the given header.
     */
    #[\Override]
    public function getHeader(string $name): array
    {
        $headers = $this->headers[strtolower($name)] ?: [];
        return $this->hasHeader($name)
            ? array_merge(...array_values($headers))
            : [];
    }

    /**
     * Retrieves a comma-separated string of the values for a single header.
     * @param string $name Case-insensitive header field name.
     * @return string A string of values as provided for the given header.
     */
    #[\Override]
    public function getHeaderLine(string $name): string
    {
        return implode(',', $this->getHeader($name));
    }

    /**
     * Return an instance with the provided value replacing the specified header.
     * @param string $name Case-insensitive header field name.
     * @param string|string[] $value Header value(s).
     * @return static
     * @throws \InvalidArgumentException for invalid header names or values.
     */
    #[\Override]
    public function withHeader(string $name, mixed $value): self
    {
        $new = clone $this;
        if ($this->hasHeader($name)) {
            unset($new->headers[strtolower($name)]);
        }
        $new->handleHeader($name, $value);
        return $new;
    }

    /**
     * Return an instance with the specified header appended with the given value.
     * @param string $name Case-insensitive header field name to add.
     * @param string|string[] $value Header value(s).
     * @return static
     * @throws \InvalidArgumentException for invalid header names.
     * @throws \InvalidArgumentException for invalid header values.
     */
    #[\Override]
    public function withAddedHeader(string $name, mixed $value): self
    {
        $new = clone $this;
        $new->handleHeader($name, $value);
        return $new;
    }

    /**
     * Return an instance without the specified header.
     * @param string $name Case-insensitive header field name to remove.
     * @return static
     */
    #[\Override]
    public function withoutHeader(string $name): self
    {
        $new = clone $this;
        if ($this->hasHeader($name)) {
            unset($new->headers[strtolower($name)]);
        }
        return $new;
    }

    /**
     * Not implemented, WebSocket only use headers.
     */
    #[\Override]
    public function getBody(): StreamInterface
    {
        throw new BadMethodCallException("Not implemented.");
    }

    /**
     * Not implemented, WebSocket only use headers.
     */
    #[\Override]
    public function withBody(StreamInterface $body): self
    {
        throw new BadMethodCallException("Not implemented.");
    }

    public function getAsArray(): array
    {
        $lines = [];
        foreach ($this->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                $lines[] = "{$name}: {$value}";
            }
        }
        return $lines;
    }

    private function handleHeader(string $name, mixed $value): void
    {
        if (!preg_match('|^[0-9a-zA-Z#_-]+$|', $name)) {
            throw new InvalidArgumentException("'{$name}' is not a valid header field name.");
        }
        $value = is_array($value) ? $value : [$value];
        foreach ($value as $content) {
            if (!is_string($content) && !is_numeric($content)) {
                throw new InvalidArgumentException("Invalid header value(s) provided.");
            }
            $content = trim($content);
            $this->headers[strtolower($name)][$name][] = $content;
        }
    }

    #[\Override]
    public function __toString(): string
    {
        return static::class;
    }

    protected function stringable(string $format, mixed ...$values): string
    {
        return sprintf("%s({$format})", static::class, ...$values);
    }
}
