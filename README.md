# Brash/Websocket

![example workflow](https://github.com/gabrielberthier/kit-websocket/actions/workflows/tests.yml/badge.svg)

PHP has a number of REALLY good libraries to handle the WebSocket Protocol out there - some of which I solely based this project upon. This package/library/project offers a number of features present in those, using ReactPHP as its asyncronous handler.
I developed this project as a way to understand in depth how the protocol worked in real life and how I could create something more lower-level with it without the need to use something more lower-level or too much abstracted, such as Socket.io and others.
Build your application using small, reusable and atomic components if you want or simply drop a single handler for incoming data, this library will handle compression, deflation and connection for you.

---

- [IMPORTANT](#important)
- [Install](#install)
- [How to](#how-to)
- [Requirements](#requirements)
- [Configuration](#configuration)
  - [Connection Object)](#connection-object)
  - [WebSocket Secured (alias WSS)](#websocket-secured-alias-wss)
- [Features Checklist + Challenges](#features-checklist-challenges)
- [Understanding The Protocol](#understanding-the-protocol)
- [Based on](#based-on)

<a name="brashwebsocket"></a>

## IMPORTANT

This library is server-side only. I did not have interest in implementing client code, but feel free to do it if you are into that ;)

## Install

```bash
composer require brash/websocket
```

## How to

Quick start:

```php

require_once 'vendor/autoload.php';

$server = new \Brash\Websocket\WsServer(host: '0.0.0.0', port: 1337);

$server->setConnectionHandler(
    connectionHandlerInterface: new class extends \Brash\Websocket\Message\Protocols\AbstractTextMessageHandler {

    private array $connections;

    public function __construct()
    {
        $this->connections = [];
    }

    // Optional, as with on onDisconnect, onError
    public function onOpen(\Brash\Websocket\Connection\Connection $connection): void
    {
        $this->connections[] = $connection;
    }

    public function handleTextData(string $data, \Brash\Websocket\Connection\Connection $connection): void
    {
        $connection->getLogger()->debug("IP" . ":" . $connection->getIp() . PHP_EOL);
        $connection->getLogger()->debug("Data: " . $data . PHP_EOL);
        $broadcast = array_filter($this->connections, fn($conn) => $conn !== $connection);

        foreach ($broadcast as $conn) {
            $conn->writeText($data);
        }
    }
    }
);
$server->start();
```

Whether using this library as a standalone solution or on top of other ReactPHP libraries (e.g, HTTP Server), the important piece is the declaration of a handler to execute real-time busineess logic.

## Requirements

- PHP 8.3^

## Configuration

You can create a configuration object using the constructor of from the static method `createFromArray`.

```php
$configArray = [
    'timeout' => 5, // In seconds
    'maxPayloadSize' => 524288, //(0.5MiB)
    'maxMessagesBuffering' => 100, // 5mb in messages
    'writeMasked' => false,
    'prod' => true,
    'ssl' => false,
    'certFile' => '',
    'passphrase' => '',
    'sslContextOptions' => [],
];

$config = \Brash\Websocket\Config\Config::createFromArray($configArray);

// OR
$config = new \Brash\Websocket\Config\Config(
    // DEFAULTS: int $timeout = self::MESSAGE_TIMEOUT_IN_SECONDS,
    // DEFAULTS: int $maxPayloadSize = self::MAX_PAYLOAD_SIZE,
    // DEFAULTS: int $maxMessagesBuffering = self::MAX_MESSAGES_BUFFERING,
    // DEFAULTS: bool $writeMasked = false,
    // DEFAULTS: bool $prod = true,
    // DEFAULTS: ?\Brash\Websocket\Config\SslConfig $sslConfig = null
);

$server = new \Brash\Websocket\WsServer(host: '0.0.0.0', port: 1337, $config);

```

### Connection Object

The `Connection` object has the following methods you can use:
- `getIp(): string`
- `getLogger(): LoggerInterface`
- `getSocketWriter(): MessageWriter`
- `write(string|Frame $frame, FrameTypeEnum $frameTypeEnum): void`
- `writeText(string|Frame $frame): void`
- `writeBinary(string|Frame $frame): void`

### WebSocket Secured (alias WSS)

In new apps you often use https. So you should use wss with WebSockets to secure data exchange. Woketo
supports wss out of the box, you just need to add the related options (`ssl` and `certFile`).

You should instanciate woketo like this:

```php
$server = new \Brash\Websocket\WsServer(9001, '127.0.0.1',
    new Config(
        ...[
            'ssl' => true,
            'certFile' => 'path/to/certificate/cert.pem',
            'sslContextOptions' => [
                'verify_peer' => false,
                'allow_self_signed' => true
            ]
        ]
    )
);
```

> Why is there only one cert file required while I have 2 files (cert and private key) ?

PHP uses a PEM formatted certificate that contains the certificate _and_ the private key.

Here is a way to generate your PEM formatted certificate for a local usage:

```bash
openssl req -x509 -nodes -days 365 -newkey rsa:2048 -keyout acme.key -out acme.crt
cat acme.key > acme.pem
cat acme.crt >> acme.pem
```

## Features Checklist + Challenges

- Web Socket Server
  - Receiving data
    - [x] Establishes handshake connections according to the Web Socket protocol
    - [x] Receives masked data payloads
    - [x] Receives TEXT and BINARY frames
    - [x] Decodes 7-bits long data payloads
    - [x] Decodes 16-bits long data payloads
    - [x] Decodes 64-bits long data payloads
    - [x] Uses permessage-deflate with zlib to handle inflate out-of-the-box.
    - [x] WSS Support (WebSocket over SSL).
  - Replying (see example/vue-client/SocketChat)
    - [x] Builds data frames according to the Web Socket protocol
    - [x] Sends 7-bits long unmasked data payloads
    - [x] Sends 16-bits long unmasked data payloads
    - [x] Sends 64-bits long unmasked data payloads
    - [x] Responds deflate messages with zlib out-of-the-box.

## Understanding The Protocol

This project relies on heavy documentation, tests and other repositories. You can read more on:

- [MDN](https://developer.mozilla.org/pt-BR/docs/Web/API/WebSockets_API/Writing_WebSocket_servers)
- [Erick Wendel's Blog](https://blog.erickwendel.com.br/implementing-the-websocket-protocol-from-scratch-using-nodejs#heading-unmasking-the-data)
  - I could spend a lot of time explaining every single detail about this implementation, but luckly someone else has made it before I would :D
- [Masking, Fragments and Stuff](https://www.openmymind.net/WebSocket-Framing-Masking-Fragmentation-and-More/)
- [MDN](https://developer.mozilla.org/pt-BR/docs/Web/API/WebSockets_API/Writing_WebSocket_servers)

## Based on

- [HEAVILY BASED ON Woketo](https://github.com/Nekland/Woketo/)
- [Websocket Client and Server for PHP](https://github.com/sirn-se/websocket-php/tree/v3.1-main)
- [AMP Websocket Server](https://github.com/amphp/websocket-server/)
- [Ratchet](https://github.com/ratchetphp/Ratchet)

## Versioning

This package follows the semver semantic versioning specification.