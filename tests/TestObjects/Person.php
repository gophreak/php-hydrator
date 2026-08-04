<?php

declare(strict_types=1);

namespace Tests\TestObjects;

final readonly class Person
{
    public function __construct(
        public string $name,
        public int $age,
        public string $email,
    ) {}
}
