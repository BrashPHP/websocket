<?php

namespace Tests\Unit;

use function Brash\Websocket\functions\getBytes;

test('Should receive converted byte string array', function (): void {
    $result = getBytes('this is a test');

    expect($result)->toBeArray();
    expect($result)->each->toBeInt();
    expect(array_reduce(
        $result,
        fn($carry, $item) => $carry . \chr($item)
    ))
        ->toBe('this is a test');
});
