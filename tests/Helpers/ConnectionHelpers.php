<?php

namespace Tests\Helpers;

use Kit\Websocket\Frame\FrameFactory;
use Kit\Websocket\Message\MessageWriter;
use React\Socket\ConnectionInterface;
use Mockery\MockInterface;

function createMessageWriter(
    ?FrameFactory $frameFactory = null,
    ?ConnectionInterface $connectionInterface = null,
    bool $writeMasked = false,
): MessageWriter {
    /** @var ConnectionInterface|MockInterface */
    $mockServer = mock(ConnectionInterface::class);
    
    return new MessageWriter(
        frameFactory: $frameFactory ?? new FrameFactory(),
        socket: $connectionInterface ?? $mockServer,
        writeMasked: $writeMasked,
    );
}