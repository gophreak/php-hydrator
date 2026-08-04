<?php

declare(strict_types=1);

namespace Hydrator\Strategies;

final readonly class MappedNameStrategy implements NamingStrategy
{
    /**
     * Maps the input key to the property name in the target class. If the key is not found,
     * the original key is used. The format should be '<property>': '<input_key>'.
     *
     * @param array<string, string> $mapping
     */
    public function __construct(
        private array $mapping,
    ) {}

    public function resolve(string $property): string
    {
        return $this->mapping[$property] ?? $property;
    }
}
