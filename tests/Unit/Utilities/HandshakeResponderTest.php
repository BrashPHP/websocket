<?php

use Kit\Websocket\Utilities\HandshakeResponder;


test('should return a valid handshake response ', function (): void {
  $sut = new HandshakeResponder();
  $response = $sut->prepareHandshakeResponse('any-id');

  expect($response)->toBeString();
  expect(explode('\r\n', $response))->toMatchArray([
    'HTTP/1.1 101 Switching Protocols',
    'Upgrade: websocket',
    'Connection: Upgrade',
    "sec-webSocket-accept: any-id",
    // This empty line MUST be present for the response to be valid
    ''
  ]);
});

