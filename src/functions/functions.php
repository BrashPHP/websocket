<?php

declare(strict_types=1);

namespace Brash\Websocket\functions;


if (!function_exists('println')) {
    function println(string $str): void
    {
        echo $str . PHP_EOL;
    }
}

if (!function_exists('removeStart')) {
    function removeStart(string $str, string $toRemove): string
    {
        if (!startsWith($str, $toRemove)) {
            return $str;
        }
        $sizeToRemove = strlen($toRemove);

        return substr($str, $sizeToRemove, strlen($str) - $sizeToRemove);
    }
}

if (!function_exists('startsWith')) {
    function startsWith($str, $start)
    {
        $length = strlen((string) $start);

        return substr((string) $str, 0, $length) === $start;
    }

}


