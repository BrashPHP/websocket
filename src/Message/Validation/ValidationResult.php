<?php

declare(strict_types=1);

namespace Kit\Websocket\Message\Validation;

use Kit\Websocket\Message\Message;

final readonly class ValidationResult
{
    public function __construct(
        public ?Message $successfulMessage = null,
        public ?\Exception $error = null
    ) {
    }

    public function success(): bool{
        return is_null($this->error);
    }
}
