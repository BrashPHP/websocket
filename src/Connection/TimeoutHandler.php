<?php

declare(strict_types=1);

namespace Kit\Websocket\Connection;

use React\EventLoop\LoopInterface;
use React\EventLoop\TimerInterface;
use React\Promise\Deferred;
use React\Promise\Promise;
use React\Promise\PromiseInterface;

final class TimeoutHandler
{
    private ?TimerInterface $timer = null;
    private Deferred $deferred;
    private \Closure $timeoutAction;

    public function __construct(
        private LoopInterface $loop,
        private int $timeoutSeconds
    ) {
        $this->timeoutAction = fn() => new \DomainException('NotImplementedYet');
    }

    public function handleConnectionTimeout(PromiseInterface $promise): void{
        if ($this->timer !== null) {
            $this->loop->cancelTimer($this->timer);
            $this->timer = null;
        }
        $promise->then($this->startTimeout(...));
    }

    /**
     * Checks if a timeout is already in progress. If so, cancels it and creates a new promise.
     */
    public function checkTimeout(): Promise
    {
        if ($this->timer !== null) {
            $this->deferred->promise()->cancel();
            $this->deferred = $this->createDeferred();
        }

        return $this->deferred->promise();
    }

    /**
     * Starts the timeout with a default duration, resolving the deferred promise upon completion.
     */
    public function startTimeout(): void
    {
        // Clear any existing timer before starting a new one
        $this->clearTimer();

        // Set a new timeout
        $this->timer = $this->loop->addTimer(
            $this->timeoutSeconds,
            $this->timeoutAction
        );
    }

    public function setTimeoutAction(\Closure $action){
        $this->timeoutAction = $action;
    }

    /**
     * Creates a new deferred object with a canceller function to handle timeout cancellation.
     */
    private function createDeferred(): Deferred
    {
        $deferred = new Deferred(canceller: $this->clearTimer(...));
        $deferred->promise()->then($this->timeoutAction);

        return $deferred;
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
