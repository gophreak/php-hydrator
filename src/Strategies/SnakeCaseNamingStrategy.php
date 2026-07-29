<?php

declare(strict_types=1);

namespace Hydrator\Strategies;

final readonly class SnakeCaseNamingStrategy implements NamingStrategy
{
    public function resolve(string $property): string
    {
        $property = preg_replace(
            '/([a-z\d])([A-Z])/',
            '$1_$2',
            $property
        );

        $property = preg_replace(
            '/([A-Z]+)([A-Z][a-z])/',
            '$1_$2',
            $property
        );

        return strtolower($property);
    }
}
