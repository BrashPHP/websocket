<?php

namespace Kit\Websocket\Http;
use Psr\Http\Message\RequestInterface;

final class RequestStringfy
{
    public function getRequestAsString(RequestInterface $requestInterface): string
    {
        $method = $requestInterface->getMethod();
        $uri = $requestInterface->getUri()->__toString();
        $port = $requestInterface->getUri()->getPort();
        $host = $requestInterface->getUri()->getHost();

        $request = mb_strtoupper($method) . ' ' . $uri . " HTTP/1.1\r\n";
        $request .= 'Host: ' . $host . ($port ? ":$port" : '') . "\r\n";
        $request .= 'User-Agent: Kitsune/1.0.1\r\n';
        $request .= "Upgrade: websocket\r\n";
        $request .= "Connection: Upgrade\r\n";

        foreach ($requestInterface->getHeaders() as $key => $header) {
            $request .= "$key: $header \r\n";
        }

        $request .= "\r\n";

        return $request;
    }
}


