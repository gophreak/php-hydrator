<?php

declare(strict_types=1);

namespace Tests\TestObjects;

final readonly class ClassWithDateUnionDataType
{
    public function __construct(
        public string $name,
        public \DateTime|string $mixed,
    ) {}
}
