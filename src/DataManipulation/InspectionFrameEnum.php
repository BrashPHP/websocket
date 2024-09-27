<?php

namespace Kit\Websocket\DataManipulation;

/**
 * Mode from to is the default mode of inspection of frames.
 * But PHP usually uses from and length to inspect frames.
 */
enum InspectionFrameEnum: int
{
    case MODE_FROM_TO = 0;
    case MODE_PHP = 1;

    /**
     * Proxy to the substr to be sure to be use the right method (mb_substr)
     *
     * @param string $frame
     * @param int    $from
     * @param int    $to
     * @return string
     */
    public function getBytesFromToString(string $frame, int $from, int $to)
    {
        $lenght = $this->value === 0 ? $to - $from + 1 : $to;

        return \mb_substr($frame, $from, $lenght, '8bit');
    }
}
