<?php

declare(strict_types=1);

namespace Brash\Websocket\Message\Validation;
use Brash\Websocket\Frame\Enums\FrameTypeEnum;
use Brash\Websocket\Frame\Exceptions\ProtocolErrorException;
use Brash\Websocket\Frame\Frame;
use Brash\Websocket\Message\Message;

final class ValidateFrame extends AbstractMessageValidator
{
    #[\Override]
    public function validate(Message $message, Frame $frame): ValidationResult
    {
        $exception = null;
        
        if ($frame->getOpcode() === FrameTypeEnum::Continuation && !$message->hasFrames()) {
            $exception = new ProtocolErrorException('The first frame cannot be a continuation frame');
        }

        if ($frame->getOpcode() !== FrameTypeEnum::Continuation && $message->hasFrames()) {
            $exception = new ProtocolErrorException('A non-continuation frame cannot follow fragmented frames');
        }

        if (is_null($exception)) {
            return parent::validate($message, $frame);
        }
        
        return new ValidationResult(error: $exception);
    }
}
