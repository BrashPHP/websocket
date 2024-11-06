<?php

declare(strict_types=1);

namespace Kit\Websocket\Connection;

use React\EventLoop\LoopInterface;
use React\EventLoop\TimerInterface;
use React\Promise\Deferred;
use React\Promise\Promise;
use React\Promise\PromiseInterface;

class TimeoutHandler
{
    private ?TimerInterface $timer = null;

    public function __construct(
        private readonly LoopInterface $loop,
        private readonly int $timeoutSeconds
    ) {
    }

    public function handleConnectionTimeout(PromiseInterface $promise): void
    {
        if ($this->timer !== null) {
            $this->loop->cancelTimer($this->timer);
            $this->timer = null;
        }
        $promise->then($this->startTimeout(...));
    }

    /**
     * Starts the timeout with a default duration, resolving the deferred promise upon completion.
     */
    public function startTimeout(Connection $connection): void
    {
        // Clear any existing timer before starting a new one
        $this->clearTimer();

        // Set a new timeout
        $this->timer = $this->loop->addTimer(
            $this->timeoutSeconds,
            fn() => $connection->timeout()
        );
    }
    /**
     * Cancels the current timer, if it exists.
     */
    private function clearTimer(): void
    {
        if ($this->timer !== null) {
            $this->loop->cancelTimer($this->timer);
            $this->timer = null;
        }
    }
}
