<?php

namespace Tests\Unit\Frame\DataManipulation;

use Brash\Websocket\Frame\DataManipulation\Functions\ByteSequenceFunction;
use Brash\Websocket\Frame\DataManipulation\Functions\GetNthByteFunction;


test("Should retrive subframes", function ($frame, $from, $to, $force8bytes = false, $res = null): void {
    if (!is_int($res)) {
        expect(fn() => ByteSequenceFunction::bytesFromTo($frame, $from, $to, $force8bytes))->toThrow($res);
    } else {
        expect($res)->toEqual(ByteSequenceFunction::bytesFromTo($frame, $from, $to, $force8bytes));
    }
})->with('frameProvider');

test("Should get nth bytes", function ($bytes, $n, $res = null): void {
    if ($res === null) {
        $this->expectException('\InvalidArgumentException');
    }

    $realRes = GetNthByteFunction::nthByte($bytes, $n);

    $this->assertEquals($res, $realRes);
})->with('bytesInFramesDataProvider');