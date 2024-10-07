<?php

declare(strict_types=1);

namespace Kit\Websocket\Frame\DataManipulation\Functions;

use Kit\Websocket\Frame\Enums\InspectionFrameEnum;

final class BytesFromToStringFunction 
{
    /**
     * Proxy to the substr to be sure to be use the right method (mb_substr)
     *
     * @param string $frame
     * @param int    $from
     * @param int    $to
     * @return string
     */
    public static function getBytesFromToString(string $frame, int $from, int $to, InspectionFrameEnum $inspectionFrameEnum): string
    {
        $length = match ($inspectionFrameEnum) {
            InspectionFrameEnum::MODE_FROM_TO => $to - $from + 1 ,
            InspectionFrameEnum::MODE_PHP => $to,
        };

        return \mb_substr($frame, $from, $length, '8bit');
    }
}

