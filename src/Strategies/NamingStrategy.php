<?php

declare(strict_types=1);

namespace Hydrator\Strategies;

interface NamingStrategy
{
    public function resolve(string $property): string;
}
