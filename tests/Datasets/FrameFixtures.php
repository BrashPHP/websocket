<?php

namespace Tests\Datasets;
use function Brash\Websocket\functions\hexArrayToString;

$invalidArgumentExceptionClass = '\InvalidArgumentException';

dataset(name: 'frameProvider', dataset: [
    [16711850, 2, 2, false, 170],
    [16711850, 0, 1, false, 65280],
    [16711850, 0, 2, false, 16711850],
    [-16711850, 1, 2, false, $invalidArgumentExceptionClass ],
    [16711850, 1, 3, false, $invalidArgumentExceptionClass ],
    ['abcdef', 1, 2, false, 25187],
    ['abcdef', 1, 3, false, 6447972],
    ['abc', 2, 5, false, $invalidArgumentExceptionClass ],
    ['abc', 1, 3, false, $invalidArgumentExceptionClass ],
    ['abc', 0, 0, false, 97],
    [
        hexArrayToString(
            ['81', '85', '37', 'fa', '21', '3d', '7f', '9f', '4d', '51', '58']
        ),
        3,
        6,
        false,
        4196482431
    ],
    [
        hexArrayToString(
            ['80', '00', '00', '00', '00', '00', '00', '00', 'a5', '45']
        ),
        0,
        7,
        true,
        (int) -9223372036854775808.00
    ],
]);
