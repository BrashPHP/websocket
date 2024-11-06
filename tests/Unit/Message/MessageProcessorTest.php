<?php

declare(strict_types=1);

namespace Tests\Unit\Message;

use Kit\Websocket\Frame\Enums\CloseFrameEnum;
use Kit\Websocket\Frame\Enums\FrameTypeEnum;
use Kit\Websocket\Frame\FrameFactory;
use Kit\Websocket\Message\Handlers\PingFrameHandler;
use Kit\Websocket\Message\Message;
use Kit\Websocket\Message\MessageFactory;
use Kit\Websocket\Message\MessageProcessor;
use Kit\Websocket\Message\MessageWriter;
use Mockery\MockInterface;
use React\Socket\ConnectionInterface;
use function Kit\Websocket\functions\hexArrayToString;
use function Tests\Helpers\createMessageWriter;

function createMockConnection(): MockInterface|ConnectionInterface
{
    /** @var ConnectionInterface|\Mockery\MockInterface */
    return mock(ConnectionInterface::class);
}

function createSut(): MessageProcessor
{
    /** @var ConnectionInterface|\Mockery\MockInterface */
    $mockServer = mock(ConnectionInterface::class);
    $mockServer->shouldReceive('write')->withAnyArgs();
    $mockServer->shouldReceive('end')->withAnyArgs();
    $messageWriter = createMessageWriter(connectionInterface: $mockServer);
    $messageFactory = new MessageFactory(maxMessagesBuffering: null);

    return new MessageProcessor($messageWriter, $messageFactory);
}

test('should build many messages with only one frame data', function (): void {
    $multipleFrameData = hexArrayToString(
        [
            '01',
            '03',
            '48',
            '65',
            '6c', // Data part 1
            '80',
            '02',
            '6c',
            '6f',       // Data part 2
            '81',
            '85',
            '37',
            'fa',
            '21',
            '3d',
            '7f',
            '9f',
            '4d',
            '51',
            '58'
        ] // Another message (Hello frame)
    );
    $processor = createSut();

    $messages = iterator_to_array($processor->process($multipleFrameData));

    expect($messages)->toHaveCount(2);
    expect($messages[1]->getContent())->toEqual('Hello');
});

test('Should receive ContinueFrameEvaluation After Control Frame', function (): void {
    $multipleFrameData = hexArrayToString(
        [
            '89',
            '00',                                                       // Ping
            '81',
            '85',
            '37',
            'fa',
            '21',
            '3d',
            '7f',
            '9f',
            '4d',
            '51',
            '58', // Another message (Hello frame)
            '89',
            '00'
        ]                                                       // Ping
    );

    $processor = createSut();

    $messages = iterator_to_array($processor->process($multipleFrameData));

    expect($messages)->toHaveCount(3);
    expect($messages[1]->getContent())->toEqual('Hello', );
    expect($messages[0]->getFirstFrame()->getOpcode())->toEqual(FrameTypeEnum::Ping, );
    expect($messages[2]->getFirstFrame()->getOpcode())->toEqual(FrameTypeEnum::Ping, );
});

test('Should test if build partial message', function (): void {
    $processor = createSut();

    $messages = iterator_to_array($processor->process(
        // "Hel" normal frame unmasked
        hexArrayToString(['01', '03', '48', '65', '6c']),
    ));


    expect($messages[0]->isComplete())->toBeFalse();

    $messages = iterator_to_array($processor->process(
        // "lo" normal frame unmasked
        hexArrayToString(['80', '02', '6c', '6f']),
        $messages[0]
    ));

    expect($messages[0]->isComplete())->toBeTrue();
    expect($messages[0]->getContent())->toEqual('Hello');
});

test('Should build only complete messages', function (): void {
    $sut = createSut();
    $messages = iterator_to_array($sut->process(
        // "Hel" and "lo" normal frame unmasked
        hexArrayToString(['01', '03', '48', '65', '6c', '80', '02', '6c', '6f']),
    ));
    expect($messages)->toHaveCount(1);
});

test('Should assure that writes frames', function (): void {
    $mockServer = createMockConnection();
    $expectedString = hexArrayToString(['81', '05', '48', '65', '6c', '6c', '6f']);
    $mockServer->expects('write')->with($expectedString);
    $mockServer->shouldReceive('end')->withAnyArgs();

    $writer = createMessageWriter(connectionInterface: $mockServer);
    $writer->writeTextFrame('Hello');
});

test('Should achieve a limitation exception due to large message', function (): void {
    $mockServer = createMockConnection();
    $factory = new FrameFactory();
    $messageWriter = createMessageWriter(connectionInterface: $mockServer);
    $processor = new MessageProcessor($messageWriter, new MessageFactory(maxMessagesBuffering: 2));
    $closeFrame = $factory->createCloseFrame(CloseFrameEnum::CLOSE_TOO_BIG_TO_PROCESS);
    $mockServer->shouldReceive('write')->with($closeFrame->getRawData());
    $mockServer->shouldReceive('end')->withAnyArgs();
    $longMessageFrame = hexArrayToString(
        ['79', '7f', 'ff', 'ff', 'ff', 'ff', 'ff', 'ff', 'ff', 'ff']
    );


    $messages = iterator_to_array($processor->process(
        $longMessageFrame,
    ));

    expect($messages)->toBeEmpty();
});

test('Should support message in many frames', function (): void {
    $multipleFrameData = hexArrayToString(
        [
            '01',
            '03',
            '48',
            '65',
            '6c', // Data part 1
            '80',
            '02',
            '6c',
            '6f'
        ]// Data part 2
    );

    $factory = new FrameFactory();

    $frame1 = $factory->newFrameFromRawData(hexArrayToString(['01', '03', '48', '65', '6c']));
    $frame2 = $factory->newFrameFromRawData(hexArrayToString(['80', '02', '6c', '6f']));

    $processor = createSut();

    $expectedMessage = new Message();
    $expectedMessage->addFrame($frame1);
    $expectedMessage->addFrame($frame2);

    $messages = iterator_to_array($processor->process(
        $multipleFrameData,
    ));

    $this->assertSame($expectedMessage->getContent(), $messages[0]->getContent());
    $this->assertTrue($messages[0]->isComplete());
    $this->assertSame($expectedMessage->isComplete(), $messages[0]->isComplete());
    $this->assertSame(FrameTypeEnum::Text, $messages[0]->getOpcode());
    $this->assertSame('Hello', $messages[0]->getContent());
    $this->assertCount(2, $messages[0]->getFrames());
});

test('Should throw incomplete message and specify incomplete message', function (): void {
    $incompleteFrame = hexArrayToString(['81', '85', '37', 'fa', '21', '3d']);
    $processor = createSut();
    $messages = iterator_to_array($processor->process(
        $incompleteFrame,
    ));

    expect($messages[0]->isComplete())->toBeFalse();
});

test('Should should process ping between two text frames', function (): void {
    // bin-frame containing :
    // 1- partial text ws-frame (containing fragment1)
    // 2- ping ws-frame (containing ping payload)
    // 3- partial (end) text ws-frame (containing fragment2)
    $multipleFrameData = hexArrayToString([
        // Frame 1 (fragment1)
        '01',
        '89',
        'b1',
        '62',
        'd1',
        '9d',
        'd7',
        '10',
        'b0',
        'fa',
        'dc',
        '07',
        'bf',
        'e9',
        '80',
        // Frame 2 (ping)
        '89',
        '8c',
        '0e',
        'be',
        '06',
        '0d',
        '7e',
        'd7',
        '68',
        '6a',
        '2e',
        'ce',
        '67',
        '74',
        '62',
        'd1',
        '67',
        '69',
        // Frame 3 (fragment2)
        '80',
        '89',
        'b3',
        'b9',
        'b9',
        '7f',
        'd5',
        'cb',
        'd8',
        '18',
        'de',
        'dc',
        'd7',
        '0b',
        '81'
    ]);

    $processor = createSut();

    $messages = iterator_to_array($processor->process(
        $multipleFrameData,
    ));
    expect('ping payload')->toEqual($messages[0]->getContent());
    expect('fragment1fragment2')->toEqual($messages[1]->getContent());
    expect($messages[0]->isComplete())->toBeTrue();
    expect($messages[1]->isComplete())->toBeTrue();
    expect($messages[0]->isComplete())->toBeTrue();
    expect($messages[1]->isComplete())->toBeTrue();
    expect($messages[0]->getOpcode())->toEqual(FrameTypeEnum::Ping);
    expect($messages[1]->getOpcode())->toEqual(FrameTypeEnum::Text);
    expect($messages[0]->getFrames())->toHaveCount(1);
    expect($messages[1]->getFrames())->toHaveCount(2);
});

test('Should catch wrong continuation frame exception', function (): void {
    $mockServer = createMockConnection();
    $frameFactory = new FrameFactory();
    $processor = new MessageProcessor(createMessageWriter(
        connectionInterface: $mockServer,
    ), messageFactory: new MessageFactory(null));
    $closeFrame = $frameFactory->createCloseFrame(CloseFrameEnum::CLOSE_PROTOCOL_ERROR);
    $mockServer->shouldReceive('write')->with($closeFrame->getRawData());
    $mockServer->shouldReceive('end')->withAnyArgs();

    $messages = iterator_to_array($processor->process(
        hexArrayToString(['80', '98', '53', '3d', 'b9', 'b3', '3d', '52', 'd7', '9e', '30', '52', 'd7', 'c7', '3a', '53', 'cc', 'd2', '27', '54', 'd6', 'dd', '73', '4d', 'd8', 'ca', '3f', '52', 'd8', 'd7']),
    ));

    expect($messages)->toBeEmpty();
});

test(
    'Should catch wrong text fragmented frame exception',
    function (): void {
        $multipleFrameData = hexArrayToString([
            '01',
            '89',
            'b1',
            '62',
            'd1',
            '9d',
            'd7',
            '10',
            'b0',
            'fa',
            'dc',
            '07',
            'bf',
            'e9',
            '81', // first frame
            '81',
            '8c',
            '0e',
            'be',
            '06',
            '0d',
            '7e',
            'd7',
            '68',
            '6a',
            '2e',
            'ce',
            '67',
            '74',
            '62',
            'd1',
            '67',
            '69',
            '80' // second frame
        ]);
        $mockServer = mock(ConnectionInterface::class);
        $frameFactory = new FrameFactory();
        $processor = new MessageProcessor(
            messageWriter: createMessageWriter($frameFactory, $mockServer),
            messageFactory: new MessageFactory(maxMessagesBuffering: 2)
        );
        $closeFrame = $frameFactory->createCloseFrame(CloseFrameEnum::CLOSE_PROTOCOL_ERROR);
        $mockServer->shouldReceive('write')->with($closeFrame->getRawData());
        $mockServer->shouldReceive('end')->withAnyArgs();


        $messages = iterator_to_array($processor->process(
            $multipleFrameData,
        ));

        expect($messages)->toBeEmpty();
    }
);
