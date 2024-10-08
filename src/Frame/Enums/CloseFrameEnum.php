<?php

declare(strict_types=1);

namespace Kit\Websocket\Frame\Enums;

enum CloseFrameEnum: int
{
    // Close frame codes according to RFC 6455
    case CLOSE_NORMAL = 1000;
    case CLOSE_GOING_AWAY = 1001;
    case CLOSE_PROTOCOL_ERROR = 1002;
    case CLOSE_WRONG_DATA = 1003;
    case CLOSE_INCOHERENT_DATA = 1007;
    case CLOSE_POLICY_VIOLATION = 1008;
    case CLOSE_TOO_BIG_TO_PROCESS = 1009;
    case CLOSE_MISSING_EXTENSION = 1010;
    case CLOSE_UNEXPECTING_CONDITION = 1011;
    case RESERVED = 0;

    // Reserved close codes
    const array CLOSE_RESERVED = [1004, 1005, 1006, 1015];
}
