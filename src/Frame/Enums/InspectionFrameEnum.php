<?php

namespace Kit\Websocket\Frame\Enums;

/**
 * Mode from to is the default mode of inspection of frames.
 * But PHP usually uses from and length to inspect frames.
 */
enum InspectionFrameEnum: int
{
    case MODE_FROM_TO = 0;
    case MODE_PHP = 1;
}
