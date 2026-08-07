<?php

declare(strict_types=1);

namespace Hydrator\Strategies;

use Hydrator\Source;

final readonly class SnakeCaseNamingStrategy implements NamingStrategy
{
    public function resolve(Source $source, string $key): ?string
    {
        $key = (string) preg_replace(
            '/([a-z\d])([A-Z])/',
            '$1_$2',
            $key,
        );

        $key = (string) preg_replace(
            '/([A-Z]+)([A-Z][a-z])/',
            '$1_$2',
            $key,
        );

        $key = strtolower($key);

        return $source->has($key) ? $key : null;
    }
}
