<?php

declare(strict_types=1);

namespace Tests\TestObjects;

final readonly class Address
{
    public function __construct(
        public string $street,
        public string $city,
        public string $province,
        public string $postcode,
    ) {}
}
