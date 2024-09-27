<?php

use Kit\Websocket\Utilities\KeyDigest;

test('should digest id key and return valid base_64 string ', function (): void {
  $sut = new KeyDigest();
  $response = $sut->createSocketAcceptKey('any-id');

  expect(base64_decode($response))->toBeString();
  expect(bin2hex(base64_decode($response)))->toBeAlphaNumeric();
});

