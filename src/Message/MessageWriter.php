<?php

namespace Kit\Websocket\Message;

use Kit\Websocket\Frame\Enums\CloseFrameEnum;
use Kit\Websocket\Frame\Enums\FrameTypeEnum;
use Kit\Websocket\Frame\Frame;
use Kit\Websocket\Frame\FrameFactory;
use React\Socket\ConnectionInterface;

final class MessageWriter
{
    public function __construct(
        private FrameFactory $frameFactory,
        private ConnectionInterface $socket,
        private bool $writeMasked = false,
    ) {
    }

    public function write(string $data): void
    {
        $this->socket->write($data);
    }

    public function writeFrame(Frame|string $frame, FrameTypeEnum $opCode): void
    {
        if (!$frame instanceof Frame) {
            $frame = $this->frameFactory->newFrame(
                payload: $frame,
                frameTypeEnum: $opCode,
                writeMask: $this->writeMasked
            );
        }

        $this->socket->write($frame->getRawData());
    }

    public function writeTextFrame(Frame|string $frame)
    {
        $this->writeFrame($frame, FrameTypeEnum::Text);
    }


    public function getFrameFactory(): FrameFactory
    {
        return $this->frameFactory;
    }

    public function getProcessConnection(): ConnectionInterface
    {
        return $this->socket;
    }

    public function close(
        CloseFrameEnum $status = CloseFrameEnum::CLOSE_NORMAL,
        string $reason = null
    ): void {
        $this->writeTextFrame($this->frameFactory->createCloseFrame($status, $reason));

        $this->socket->end();
    }

    public function writeExceptionCode(CloseFrameEnum $closeCode)
    {
        $closeFrame = $this->frameFactory->createCloseFrame(status: $closeCode);
        $this->writeTextFrame($closeFrame);
        $this->socket->end();
    }
}