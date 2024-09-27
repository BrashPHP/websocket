<?php

declare(strict_types=1);

namespace Kit\Websocket\functions;


if (!function_exists('println')) {
    function println(string $str): void
    {
        echo $str . PHP_EOL;
    }
}


