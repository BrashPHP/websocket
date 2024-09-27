<?php

declare(strict_types=1);

namespace Kit\Websocket\functions;


if (!function_exists('getBytes')) {
    function getBytes(string $string, string $charset = 'UTF-8'): array
    {
        return \array_values(
            \unpack(
                'C*',
                mb_convert_encoding(
                    $string,
                    'UTF-8',
                    $charset
                )
            )
        );
    }
}

if (!function_exists('frameSize')) {
    /**
     * `strlen` cannot be trusted because of an option of mbstring extension, more info:
     * http://php.net/manual/fr/mbstring.overload.php
     * http://php.net/manual/fr/function.mb-strlen.php#77040
     */
    function frameSize(string $frame): int
    {
        if (\extension_loaded('mbstring')) {
            return \mb_strlen($frame, '8bit');
        }

        return \strlen($frame);
    }
}

if (!function_exists('intToBinaryString')) {
    /**
     * Take a frame represented by a decimal int to transform it in a string.
     * Notice that any int is a frame and cannot be more than 8 bytes
     */
    function intToBinaryString(int $frame, ?int $size = null): string
    {
        $format = match (true) {
            $size <= 2 => 'n*',
            $size <= 4 => 'N*',
            default => 'J*'
        };

        $res = \pack($format, $frame);

        if ($size === null) {
            $res = \ltrim($res, "\0");
        }

        return $res;
    }
}

if (!function_exists('binaryStringToHex')) {
    /**
     * Take a frame represented by a decimal int to transform it in a string.
     * Notice that any int is a frame and cannot be more than 8 bytes
     */
    function binaryStringToHex(string $frame): string
    {
        return \unpack('H*', $frame)[1];
    }
}

if (!function_exists('hexArrayToString')) {
    /**
     * Because strings are the best way to store many bytes in PHP it can
     * be useful to make the conversion between hex (which are strings)
     * array to string.
     */
    function hexArrayToString(array $hexArray): string
    {
        return array_reduce(
            $hexArray,
            fn(
            string $carry,
            string $hexNum
        ) =>
            $carry . \chr(\hexdec($hexNum)),
            ""
        );
    }
}

if (!function_exists('binaryStringtoInt')) {
    function binaryStringtoInt(string $frame): int
    {
        $len = frameSize($frame);

        if ($len > 8) {
            throw new \InvalidArgumentException(
                \sprintf('The string %s cannot be converted to int because an int cannot be more than 8 bytes (64b).', $frame)
            );
        }

        if (\in_array(frameSize($frame), [1, 3])) {
            $frame = "\0{$frame}";
        }

        $format = match (true) {
            $len <= 2 => 'n',
            $len <= 4 => 'N',
            default => 'J'
        };

        if ($format === 'J') {
            do {
                $frame = "\0{$frame}";
            } while (frameSize($frame) !== 8);
        }

        return \unpack($format, $frame)[1];
    }
}

