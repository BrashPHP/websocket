<?php

declare(strict_types=1);

namespace Brash\Websocket\Compression\Exceptions;

class CompressionErrorsCollectionException extends \RuntimeException
{
    /**
     *
     * @param \RuntimeException[] $exceptions
     */
    public function __construct(
        array $exceptions
    ) {
        $message = implode(
            '; ',
            array_map(
                fn(\RuntimeException $runtimeException) => $runtimeException->getMessage(),
                $exceptions
            )
        );

        parent::__construct("The following errors have been detected in Sec-WebSocket-Extensions header: {$message}.");
    }
}

