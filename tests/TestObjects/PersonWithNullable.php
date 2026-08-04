<?php

declare(strict_types=1);

namespace Tests\TestObjects;

final readonly class PersonWithNullable
{
    public function __construct(
        public string $name,
        public ?int $age,
        public ?string $email,
    ) {}
}
