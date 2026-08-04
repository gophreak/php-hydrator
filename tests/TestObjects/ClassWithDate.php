<?php

declare(strict_types=1);

namespace Tests\TestObjects;

final class ClassWithDate
{
    public function __construct(
        public \DateTimeImmutable $dateTimeImmutableArg1,
        public \DateTime $dateTimeArg2,
    ) {}
}
