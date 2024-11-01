<?php

namespace Tests\Unit\Connection;

use React\Promise\Deferred;
use React\Promise\Promise;
use function React\Promise\resolve;

test('Should call deferred promise only one time', function (): void {
    $deferred = new Deferred();
    $mockTester = mock();
    $mockTester->shouldReceive('exec')->withNoArgs()->times(1);
    $deferred->promise()->then(function () use ($mockTester): void {
        $mockTester->exec();
    });
    $deferred->resolve(resolve(new Promise(fn($resolve) => $resolve(42))));
    $deferred->resolve(45);
    $deferred->resolve(41);
});

