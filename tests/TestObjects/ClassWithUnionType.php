<?php

declare(strict_types=1);

namespace Tests\TestObjects;

final readonly class ClassWithUnionType
{
    public function __construct(
        public string $name,
        public int|string $mixed,
    ) {}
}
