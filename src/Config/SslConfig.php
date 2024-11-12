<?php

namespace Kit\Websocket\Config;

final readonly class SslConfig
{
    public function __construct(
        public string $certFile = '',
        public string $passphrase = '',
        public array $sslContextOptions = []
    ) {
        if (!is_file($this->certFile)) {
            throw new class extends \RuntimeException {
                public function __construct()
                {
                    parent::__construct('With ssl configuration, you need to specify a certificate file.');
                }
            };
        }
    }
}
