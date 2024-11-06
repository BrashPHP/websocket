<?php

declare(strict_types=1);

namespace Kit\Websocket\Message\Validation;
use Kit\Websocket\Frame\Frame;
use Kit\Websocket\Message\Message;

final class CanIncludeFrame extends AbstractMessageValidator
{
    #[\Override]
    public function validate(Message $message, Frame $frame): ValidationResult
    {
        $exception = $message->addFrame($frame);

        if ($message->isContinuationMessage()) {
            return new ValidationResult($message);
        }

        if (is_null($exception)) {
            return parent::validate($message, $frame);
        }
        
        return new ValidationResult(error: $exception);
    }
}
