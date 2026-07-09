<?php

declare(strict_types=1);

namespace Tests\TestObjects;

final class CastingObject
{
    public function __construct(
        public string $string,
        public int $int,
        public float $float,
        public bool $boolean,
    ) {}
}
