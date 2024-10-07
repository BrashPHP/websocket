<?php
/**
 * This file is a part of the Woketo package.
 *
 * (c) Nekland <dev@nekland.fr>
 *
 * For the full license, refer to the LICENSE file in the root directory of this project.
 */

declare(strict_types=1);

namespace Kit\Websocket\Frame;


use Kit\Websocket\Frame\DataManipulation\Functions\BytesFromToStringFunction;
use Kit\Websocket\Frame\Enums\FrameTypeEnum;

use Kit\Websocket\Frame\Enums\InspectionFrameEnum;
use function Kit\Websocket\functions\frameSize;
use function Kit\Websocket\functions\intToBinaryString;

class Frame
{

    /**
     * @param \Kit\Websocket\Frame\Enums\FrameTypeEnum $opcode
     * @param \Kit\Websocket\Frame\FrameMetadata $metadata
     * @param \Kit\Websocket\Frame\FramePayload $framePayload
     * @param int $maxPayloadSize (0.5MiB) Adjust if needed.
     */
    public function __construct(
        private FrameTypeEnum $opcode,
        private FrameMetadata $metadata,
        private FramePayload $framePayload,
        private int $maxPayloadSize = 524288
    ) {
    }

    public function getOpcode()
    {
        return $this->opcode;
    }

    public function getMetadata(): FrameMetadata
    {
        return $this->metadata;
    }

    public function getFramePayload(): FramePayload
    {
        return $this->framePayload;
    }

    public function getRawData(): string
    {
        $data = '';
        $firstLen = $this->framePayload->getFirstLength();
        $secondLen = $this->framePayload->getSecondLength();

        // Build the initial portion of the data
        $data .= $this->buildInitialDataPortion($firstLen);

        // Append second length if necessary
        if ($secondLen !== null) {
            $data .= intToBinaryString($secondLen, $firstLen === 126 ? 2 : 8);
        }

        // Handle masking
        if ($this->framePayload->isMasked()) {
            $data .= $this->framePayload->getMaskingKey();
        }

        $data .= $this->framePayload->getPayload();

        return $data;
    }

    public function isFinal(): bool
    {
        return $this->metadata->fin;
    }

    public function isControlFrame(): bool
    {
        return $this->opcode->isControlFrame();
    }

    private function buildInitialDataPortion(int $firstLen)
    {
        $newHalfFirstByte = ($this->final ?? 1) << 7;
        $newFirstByte = ($newHalfFirstByte + $this->opcode->value) << 8;
        $newSecondByte = ($this->framePayload->isMasked() << 7) + $firstLen;

        return intToBinaryString($newFirstByte + $newSecondByte);
    }

    /**
     * Returns the content and not potential metadata of the body.
     * If you want to get the real body you will prefer using `getPayload`
     *
     * @return string
     */
    public function getContent()
    {
        $payload = $this->getPayload();
        if ($this->getOpcode() === FrameTypeEnum::Text || $this->getOpcode() === FrameTypeEnum::Binary) {
            return $payload;
        }

        $len = frameSize($payload);
        if ($len !== 0 && $this->getOpcode() === FrameTypeEnum::Close) {
            return BytesFromToStringFunction::getBytesFromToString(
                $payload,
                2,
                $len,
                inspectionFrameEnum: InspectionFrameEnum::MODE_FROM_TO
            );
        }

        return $payload;
    }

    public function getPayload()
    {
        return $this->framePayload->getPayload();
    }
}