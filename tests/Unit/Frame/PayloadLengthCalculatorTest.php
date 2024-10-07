<?php

namespace Tests\Unit\Frame;

use Kit\Websocket\Frame\Exceptions\IncompleteFrameException;
use Kit\Websocket\Frame\PayloadLengthCalculator;

test('Should convert a string frame and return content', function (): void {
    $sut = new PayloadLengthCalculator();
    $byteString = chr(100);
    $result = $sut->getLength("0{$byteString}");

    expect($result)->toBe([7, 100]);
});

test('Should convert a string frame with second byte as 126 and return content based on next two bytes', function (): void {
    $sut = new PayloadLengthCalculator();
    $byteString = chr(126);
    $result = $sut->getLength("0{$byteString}11");

    expect($result)->toBe([23, 12593]);
});

test('Should convert a string frame with second byte as 127 and return content based on next eight bytes', function (): void {
    $sut = new PayloadLengthCalculator();
    $byteString = chr(127);
    $result = $sut->getLength("0{$byteString}11111111");

    expect($result)->toBe([71, 3544668469065756977]);
});

test('Should expect error when byte sequence is smaller than message frame', function (): void {
    $sut = new PayloadLengthCalculator();
    $byteString = chr(127);

    expect(fn() => $sut->getLength("0{$byteString}111111"))->toThrow(IncompleteFrameException::class);
});