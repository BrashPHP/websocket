<?php

declare(strict_types=1);

namespace Kit\Websocket\Frame\Handlers;

use Kit\Websocket\Frame\Enums\CloseFrameEnum;
use Kit\Websocket\Frame\Enums\FrameTypeEnum;
use Kit\Websocket\Frame\Frame;
use Kit\Websocket\Frame\Protocols\FrameHandlerInterface;
use Kit\Websocket\Message\Message;
use Kit\Websocket\Message\MessageProcessor;
use React\Socket\ConnectionInterface;
use function Kit\Websocket\functions\frameSize;

class CloseFrameHandler implements FrameHandlerInterface
{
    /**
     * {@inheritdoc}
     */
    public function supports(Message $message): bool
    {
        return $message->getFirstFrame()->getOpcode() === FrameTypeEnum::Close;
    }

    /**
     * {@inheritdoc}
     */
    public function process(Message $message, MessageProcessor $messageProcessor, ConnectionInterface $socket): void
    {
        $frame = $message->getFirstFrame();
        $code = $this->getCloseType($frame);
        $messageProcessor->write($messageProcessor->getFrameFactory()->createCloseFrame($code));
        $socket->end();
    }

    private function getCloseType(Frame $frame): CloseFrameEnum
    {
        $metadata = $frame->getMetadata();
        $payload = $frame->getPayload();

        if ($metadata->isUnsupportedExtension()) {
            return CloseFrameEnum::CLOSE_PROTOCOL_ERROR;
        }


        if (frameSize($payload) > 1) {
            $errorCode = ord($payload[0]) & 0xFF;
            $existingCode = CloseFrameEnum::tryFrom($errorCode);
            $isValidRange = $errorCode >= 1000 && $errorCode <= 4999;
            $isWebsocketCode = $errorCode >= 1000 && $errorCode < 3000;

            // https://tools.ietf.org/html/rfc6455#section-7.4
            $validCode = !is_null($existingCode) && $isValidRange && $isWebsocketCode;

            if (!$validCode) {
                return CloseFrameEnum::CLOSE_PROTOCOL_ERROR;
            }
        }

        return CloseFrameEnum::CLOSE_NORMAL;
    }
}
