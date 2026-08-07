<?php

declare(strict_types=1);

namespace Hydrator\KeyResolvers;

use Hydrator\Source;

interface KeyResolver
{
    public function resolve(Source $source, string $key): ?string;
}
