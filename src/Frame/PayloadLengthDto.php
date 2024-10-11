<?php

declare(strict_types=1);

namespace Kit\Websocket\Frame;
use Kit\Websocket\Frame\DataManipulation\Functions\ByteSequenceFunction;

readonly class PayloadLengthDto
{
    public function __construct(
        public int $size,
        public int $length,
        public int $threshold,
        public bool $force8Bits
    ) {
    }

    public function getRealLength(string $rawData){
        $defaultStartByte = 2;

        if ($this->length < 126) {
            return $this->length;
        }

        return ByteSequenceFunction::bytesFromTo(
            frame: $rawData,
            from: $defaultStartByte,
            to: $this->threshold,
            force8bytes: $this->force8Bits
        );
    }
}
