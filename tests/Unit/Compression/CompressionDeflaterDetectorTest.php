<?php

namespace Tests\Unit\Compression;

use Kit\Websocket\Compression\CompressionDeflaterDetector;
use Kit\Websocket\Compression\Exceptions\CompressionErrorsCollectionException;
use Kit\Websocket\Compression\Exceptions\InvalidTakeoverException;
use Kit\Websocket\Compression\Exceptions\InvalidWindowSizeException;
use Mockery\MockInterface;
use Psr\Http\Message\RequestInterface;
use Kit\Websocket\Compression\ServerCompressionContext;
use Kit\Websocket\Compression\CompressionConf;

function createRequestMock(): RequestInterface|MockInterface
{
    return mock(RequestInterface::class);
}

it('returns null if Sec-WebSocket-Extensions header is missing', function (): void {
    $request = createRequestMock();
    $request->shouldReceive("getHeaderLine")->andReturn('');

    $detector = new CompressionDeflaterDetector();

    expect($detector->detect($request))->toBeNull();
});

it('returns null if header does not start with permessage-deflate', function (): void {
    $request = createRequestMock();
    $request->shouldReceive("getHeaderLine")->andReturn('other-extension; key=value');

    $detector = new CompressionDeflaterDetector();

    expect($detector->detect($request))->toBeNull();
});

it('throws CompressionErrorsCollectionException for invalid window size', function (): void {
    $request = createRequestMock();
    $request->shouldReceive("getHeaderLine")->andReturn('permessage-deflate; server_max_window_bits=7');

    $detector = new CompressionDeflaterDetector();

    $detector->detect($request);
})->throws(CompressionErrorsCollectionException::class);

it('throws InvalidTakeoverException for invalid takeover value', function (): void {
    $request = createRequestMock();
    $request->shouldReceive('getHeaderLine')->andReturn('permessage-deflate; client_no_context_takeover=value');

    $detector = new CompressionDeflaterDetector();

    $detector->detect($request);
})->throws(CompressionErrorsCollectionException::class);

it('returns a ServerCompressionContext for valid configuration', function (): void {
    $request = createRequestMock();
    $request->shouldReceive("getHeaderLine")->andReturn('permessage-deflate; server_max_window_bits=15');

    $detector = new CompressionDeflaterDetector();

    $result = $detector->detect($request);

    expect($result)->toBeInstanceOf(ServerCompressionContext::class);
});
