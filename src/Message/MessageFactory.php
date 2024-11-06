<?php


namespace Kit\Websocket\Message;


final readonly class MessageFactory
{
    public function __construct(private ?int $maxMessagesBuffering = null)
    {
    }

    public function createMessage(): Message
    {
        return new Message(maxMessagesBuffering: $this->maxMessagesBuffering);
    }
}
