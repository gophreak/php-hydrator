<?php

declare(strict_types=1);

namespace Hydrator;

interface Source
{
    public function has(string $key): bool;

    public function get(string $key): mixed;
}
