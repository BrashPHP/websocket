<?php

declare(strict_types=1);

namespace Brash\Websocket\Message;

final class MessageBus
{
    public function __construct(private ?string $data)
    {
    }

    public function getData(): ?string
    {
        return $this->data;
    }

    public function setData(?string $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function hasValidData(): bool
    {
        return boolval($this->data);
    }
}

