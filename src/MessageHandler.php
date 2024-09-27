<?php

declare(strict_types=1);

namespace Kit\Websocket;

class MessageHandler
{
    private \Fiber $fiber;
    private bool $isRunning = false;

    public function __construct()
    {
        $isRunning = $this->isRunning;
        $this->fiber = new \Fiber(function () use ($isRunning): void{
            while ($isRunning) {
                $this->checkForUpdate();
                \Fiber::suspend();
            }
        });
    }

    public function closeHandler(){
        $this->isRunning = false;
        $this->fiber->getReturn();
    }

    private function checkForUpdate()
    {
        echo "Checking for updates";
    }


}
