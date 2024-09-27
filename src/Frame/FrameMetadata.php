<?php

namespace Kit\Websocket\Frame;

use function Kit\Websocket\functions\nthBitFromByte;
use function Kit\Websocket\functions\validateByte;

final readonly class FrameMetadata
{
    /**
     * Indicates whether this frame is final or not
     */
    public bool $fin;
    /**
     * RSVs are related to extensions used in a websocket
     */
    public bool $rsv1;
    public bool $rsv2;
    public bool $rsv3;
    

    public function __construct(private int $firstByte)
    {
        validateByte($firstByte);

        $this->fin = boolval(nthBitFromByte($this->firstByte, 1));
        $this->rsv1 = boolval(nthBitFromByte($this->firstByte, 2));
        $this->rsv2 = boolval(nthBitFromByte($this->firstByte, 3));
        $this->rsv3 = boolval(nthBitFromByte($this->firstByte, 4));
    }
}
