<?php

declare(strict_types=1);

namespace Tests\TestObjects;

final readonly class PersonSeparateName
{
    public function __construct(
        public string $firstName,
        public string $middleName,
        public string $lastName,
    ) {}
}
