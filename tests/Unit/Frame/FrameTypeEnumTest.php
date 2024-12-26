<?php

namespace Tests\Unit\Frame;

use Brash\Websocket\Frame\Enums\FrameTypeEnum;

test('Should check if enum of type text is operation', fn() => expect((FrameTypeEnum::Text)->isOperation())->toBeTrue());
test('Should check if enum of type binary is operation', fn() => expect((FrameTypeEnum::Binary)->isOperation())->toBeTrue());
test('Should check if enum outside range binary and text is not operation', fn() => expect((FrameTypeEnum::Close)->isOperation())->toBeFalse());
test('Should check if enum of type binary is from a control frame', fn() => expect((FrameTypeEnum::Binary)->isControlFrame())->toBeFalse());
test('Expect enum of value greater than 8 to be control frame', fn() => expect((FrameTypeEnum::Close)->isControlFrame())->toBeTrue());

