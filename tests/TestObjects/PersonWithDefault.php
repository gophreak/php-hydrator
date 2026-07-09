<?php

declare(strict_types=1);

namespace Tests\TestObjects;

final readonly class PersonWithDefault
{
    public function __construct(
        public string $name,
        public int $age = 21,
        public string $email = 'anonymous@example.com',
    ) {}
}
