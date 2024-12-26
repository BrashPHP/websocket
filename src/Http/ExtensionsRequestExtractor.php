<?php

namespace Brash\Websocket\Http;
use Psr\Http\Message\RequestInterface;


final class ExtensionsRequestExtractor
{
    public function getExtensions(RequestInterface $requestInterface): array
    {
        $originalHeaders = $requestInterface->getHeader('Sec-WebSocket-Extensions');
        if (!\is_array($originalHeaders)) {
            $originalHeaders = [$originalHeaders];
        }

        $extensionHeaders = [];
        $extensions = [];

        foreach ($originalHeaders as $extensionHeader) {
            $extensionHeaders = \array_merge($extensionHeaders, \array_map(
                \trim(...),
                \explode(',', $extensionHeader)
            ));
        }

        foreach ($extensionHeaders as $extension) {
            $explodingHeader = \explode(';', $extension);
            $extensionName = \trim($explodingHeader[0]);
            $extensions[$extensionName] = [];

            if (\count($explodingHeader)) {
                unset($explodingHeader[0]); // removing the name of the extension
                foreach ($explodingHeader as $variable) {
                    $explodeVariable = \explode('=', $variable);

                    // The value can be with or without quote. We need to remove extra quotes.
                    $value = \preg_replace('/^"(.+)"$/', '$1', trim($explodeVariable[1]));
                    $value = \str_replace('\\"', '"', $value);

                    $extensions[$extensionName][\trim($explodeVariable[0])] = $value;
                }
            }
        }

        return $extensions;
    }
}

