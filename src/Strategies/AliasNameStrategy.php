<?php

declare(strict_types=1);

namespace Hydrator\Strategies;

use Hydrator\Source;

final readonly class AliasNameStrategy implements NamingStrategy
{
    /**
     * Maps the input key to the property name in the target class. If the key is not found,
     * the original key is used. The format should be '<property>': ['<input_key1>',<input_key2>,'<input_key3>'].
     *
     * @param array<string, list<string>> $aliases
     */
    public function __construct(
        private array $aliases,
    ) {}

    public function resolve(Source $source, string $key): ?string
    {
        $keys = array_key_exists($key, $this->aliases) ? $this->aliases[$key] : [$key];

        return array_find($keys, fn ($key) => $source->has($key));
    }
}
