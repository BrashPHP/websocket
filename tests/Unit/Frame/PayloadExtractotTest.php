<?php
namespace Tests\Unit\Frame;
use Kit\Websocket\Frame\DataManipulation\Functions\GetNthByteFunction;

use Kit\Websocket\Frame\FrameBuilder;
use Kit\Websocket\Frame\PayloadLengthCalculator;
use Kit\Websocket\Frame\PayloadLengthDto;


function createSut(): FrameBuilder
{
    return new FrameBuilder();
}

function getLength(string $rawData): PayloadLengthDto
{
    $secondByte = GetNthByteFunction::nthByte(frame: $rawData, byteNumber: 1);
    $payloadLengthCalculator = new PayloadLengthCalculator();
    return $payloadLengthCalculator->getPayloadLength($secondByte);
}

test('Should extract masked payload based on first bit from byte string', function (): void {
    $sut = createSut();
    $byteString = chr(128); //10000000
    $payload = "0{$byteString}11ABF";
    $result = getLength($payload);

    $result = $sut->processPayload($payload, $result);
    expect($result->isMasked())->toBeTrue();
    expect($result->getMaskingKey())->toEqual("11AB");
});

test('Should extract unmasked payload based on first bit from byte string', function (): void {
    $sut = createSut();
    $byteString = chr(3); //00000011
    $payload = "0{$byteString}11ABF";
    $result = getLength($payload);

    $result = $sut->processPayload($payload, $result);
    expect($result->isMasked())->toBeFalse();
});

test('Should extract masked payload data using second byte', function (): void {
    $sut = createSut();
    $byteString = chr(130); //10000111
    $payload = "0{$byteString}11ABFFFF";
    $result = getLength($payload);

    $result = $sut->processPayload($payload, $result);
    expect($result->getLenSize())->toEqual(7);
    expect($result->getMaskingKey())->toEqual("11AB");
    expect($result->getPayload())->not->toEqual("FFF");
});

test('Should extract unmasked payload data using second byte', function (): void {
    $sut = createSut();
    $byteString = chr(8); //00001000
    $payload = "0{$byteString}11ABFFF";
    $result = getLength($payload);

    $result = $sut->processPayload($payload, $result);
    expect($result->getLenSize())->toEqual(7);
    expect($result->getMaskingKey())->toEqual("");
    expect($result->getPayload())->toEqual("11ABFFF");
});
