<?php

declare(strict_types=1);

namespace Kit\Websocket\Message\Validation;

use Kit\Websocket\Frame\Frame;
use Kit\Websocket\Frame\FrameValidation\ValidationUponOpCode;
use Kit\Websocket\Message\Message;

final class ValidateOpCode extends AbstractMessageValidator
{

    public function __construct(private ValidationUponOpCode $validationUponOpCode)
    {

    }

    public function validate(Message $message, Frame $frame): ValidationResult
    {
        $result = $this->validationUponOpCode->validate($frame);

        if (is_null($result)) {
            if ($frame->isControlFrame() && $message->hasFrames()) {
                $controlFrameMessage = new Message();
                $controlFrameMessage->addFrame($frame);

                return new ValidationResult($controlFrameMessage);
            }
            
            return $this->nextHandler->validate($message, $frame);
        }

        return new ValidationResult(error: $result);
    }
}
