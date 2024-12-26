<?php

namespace Brash\Websocket;

use Brash\Websocket\Config\SslConfig;
use React\EventLoop\LoopInterface;
use React\Socket\SecureServer;
use React\Socket\ServerInterface;

final class SslServerFactory
{
    public function createServer(ServerInterface $serverInterface, LoopInterface $loopInterface, SslConfig $config): ServerInterface
    {
        return new SecureServer(
            $serverInterface,
            $loopInterface,
            array_merge([
                'local_cert' => $config->certFile,
                'passphrase' => $config->passphrase,
            ], $config->sslContextOptions)
        );
    }
}


