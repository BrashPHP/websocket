<?php

namespace Kit\Websocket\Compression;

use Kit\Websocket\Frame\Frame;
use Kit\Websocket\Frame\FrameMetadata;
use Kit\Websocket\Frame\FramePayload;
use function Kit\Websocket\functions\frameSize;

readonly final class ServerCompressionContext
{
    private Rfc7692Compression $compressor;

    public function __construct(
        public CompressionConf $compressionConf
    ) {
        $this->compressor = new Rfc7692Compression(isServer: true, settings: $compressionConf);
    }

    public function attachToStringResponse(string $upgradeString): string
    {
        $headerValues = $this->compressionConf->getConfAsStringHeader();

        return preg_replace(
            '/(\r\n\r\n)/',
            "\r\nSec-WebSocket-Extensions: $headerValues\r\n\r\n",
            $upgradeString,
            1
        );
    }

    public function deflateFrame(Frame $frame): Frame
    {
        if ($frame->getMetadata()->rsv1) {
            return $frame; // Already deflated
        }

        return $this->processFrame(
            $frame,
            fn($payload, $isFinal) => $this->compressor->compress($payload, $isFinal)
        );
    }

    public function inflateFrame(Frame $frame): Frame
    {
        return $this->processFrame(
            $frame,
            fn($payload, $isFinal) => $this->compressor->decompress($payload, $isFinal)
        );
    }

    private function processFrame(Frame $frame, callable $processor): Frame
    {
        $payload = $processor($frame->getPayload(), $frame->isFinal());
        $payloadLen = frameSize($payload);
        $payloadLenSize = match (true) {
            $payloadLen > 126 && $payloadLen < (2 ** 16) => 23,
            $payloadLen >= (2 ** 16) => 71,
            default => 7,
        };

        return new Frame(
            $frame->getOpcode(),
            new FrameMetadata(
                fin: $frame->isFinal(),
                rsv1: true,
                rsv2: false,
                rsv3: false
            ),
            new FramePayload($payload, $payloadLen, $payloadLenSize, '')
        );
    }
}