<?php

declare(strict_types=1);

namespace Kit\Websocket\Message\Handlers;

use Kit\Websocket\Connection\Connection;
use Kit\Websocket\Frame\Enums\CloseFrameEnum;
use Kit\Websocket\Frame\Enums\FrameTypeEnum;
use Kit\Websocket\Frame\Frame;
use Kit\Websocket\Message\Message;
use Kit\Websocket\Message\Protocols\MessageHandlerInterface;
use function Kit\Websocket\functions\frameSize;

class CloseFrameHandler implements MessageHandlerInterface
{
    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function hasSupport(Message $message): bool
    {
        return $message->getOpcode() === FrameTypeEnum::Close;
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function handle(Message $message, Connection $connection): void
    {
        $frame = $message->getFirstFrame();
        $code = $this->getCloseType($frame);
        $connection->close($code);
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
