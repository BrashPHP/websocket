<?php
/**
 * This file is a part of Woketo package.
 *
 * (c) Nekland <dev@nekland.fr>
 *
 * For the full license, take a look to the LICENSE file
 * on the root directory of this project
 */

namespace Nekland\Woketo\Rfc6455\FrameHandler;


use Kit\Websocket\Frame\Enums\CloseFrameEnum;
use Kit\Websocket\Frame\Enums\FrameTypeEnum;
use Kit\Websocket\Frame\Frame;
use React\Socket\ConnectionInterface;
use function Kit\Websocket\functions\frameSize;

class CloseFrameHandler implements Rfc6455FrameHandlerInterface
{
    /**
     * {@inheritdoc}
     */
    public function supports(Message $message)
    {
        return $message->getFirstFrame()->getOpcode() === FrameTypeEnum::Close;
    }

    /**
     * {@inheritdoc}
     */
    public function process(Message $message, MessageProcessor $messageProcessor, ConnectionInterface $socket)
    {
        /**
         * @var Frame
         */
        $frame = $message->getFirstFrame();
        $code = $this->getCloseType($frame);


        $messageProcessor->write($messageProcessor->getFrameFactory()->createCloseFrame($code), $socket);
        $socket->end();
    }

    private function getCloseType(Frame $frame): CloseFrameEnum
    {
        $metadata = $frame->metadata;
        $payload = $frame->getPayload();

        if ($metadata->rsv1 || $metadata->rsv2 || $metadata->rsv3) {
            return CloseFrameEnum::CLOSE_PROTOCOL_ERROR;
        }

        if (frameSize($payload) > 1) {
            $errorCode = (0 << 8) + \ord($payload[0]);
            $existingCode = CloseFrameEnum::tryFrom($errorCode);
            $invalidCodeRange = $errorCode < 1000 || $errorCode > 4999;
            $isWebsocketCode = $errorCode > 1000 || $errorCode < 2999;

            // https://tools.ietf.org/html/rfc6455#section-7.4
            if (

                is_null($existingCode)
                && !$isWebsocketCode
                || $invalidCodeRange
            ) {
                return CloseFrameEnum::CLOSE_PROTOCOL_ERROR;
            }
        }

        return CloseFrameEnum::CLOSE_NORMAL;
    }
}