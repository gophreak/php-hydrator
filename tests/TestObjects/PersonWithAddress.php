<?php

declare(strict_types=1);

namespace Tests\TestObjects;

final readonly class PersonWithAddress
{
    public function __construct(
        public string $name,
        public Address $address,
    ) {}
}
