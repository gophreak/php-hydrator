<?php

declare(strict_types=1);

namespace Hydrator\Sources;

use Hydrator\Source;
use Psr\Http\Message\ServerRequestInterface;

final readonly class PsrRequestSource implements Source
{
    public function __construct(private ServerRequestInterface $request) {}

    public function has(string $key): bool
    {
        $body = $this->request->getParsedBody();

        if (is_array($body) && array_key_exists($key, $body)) {
            return true;
        }

        if (array_key_exists($key, $this->request->getQueryParams())) {
            return true;
        }

        return array_key_exists($key, $this->request->getAttributes());
    }

    public function get(string $key): mixed
    {
        $body = $this->request->getParsedBody();

        if (is_array($body) && array_key_exists($key, $body)) {
            return $body[$key];
        }

        $query = $this->request->getQueryParams();

        if (array_key_exists($key, $query)) {
            return $query[$key];
        }

        $attributes = $this->request->getAttributes();

        if (array_key_exists($key, $attributes)) {
            return $attributes[$key];
        }

        return null;
    }
}
