<?php

namespace Brash\Websocket\Frame;

use function Brash\Websocket\functions\nthBitFromByte;
use function Brash\Websocket\functions\validateByte;

final readonly class FrameMetadata
{
    /**
     *
     * This class contains information regarding a frame metadata, such RSVs and fin code.
     * RSVs are related to extensions used in a websocket, while fin code tells if it is a final frame.
     *
     * @param bool $fin Indicates whether this frame is final or not
     * @param bool $rsv1 RSV1 is also called "Per-Message Compressed" bit
     * @param bool $rsv2
     * @param bool $rsv3
     */
    public function __construct(
        public bool $fin,
        public bool $rsv1,
        public bool $rsv2,
        public bool $rsv3
    ) {
    }

    public static function fromByte(int $firstByte)
    {
        validateByte($firstByte);

        $fin = boolval(nthBitFromByte($firstByte, 1));
        $rsv1 = boolval(nthBitFromByte($firstByte, 2));
        $rsv2 = boolval(nthBitFromByte($firstByte, 3));
        $rsv3 = boolval(nthBitFromByte($firstByte, 4));

        return new self(
            $fin,
            $rsv1,
            $rsv2,
            $rsv3,
        );
    }

    /**
     * Checks whether the library offers support to the current extension
     *
     * @return void
     */
    public function isUnsupportedExtension(): bool
    {
        $unsupportedExtensions = [
            $this->rsv2,
            $this->rsv3
        ];

        foreach ($unsupportedExtensions as $unsupportedExtension) {
            if ($unsupportedExtension) {
                return true;
            }
        }

        return false;
    }
}
