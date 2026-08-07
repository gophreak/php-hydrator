<?php

declare(strict_types=1);

namespace Hydrator\Strategies;

use Hydrator\Source;

interface NamingStrategy
{
    public function resolve(Source $source, string $key): ?string;
}
