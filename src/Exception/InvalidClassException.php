<?php

declare(strict_types=1);

namespace Hydrator\Exception;

final class InvalidClassException extends \RuntimeException
{
    public function __construct(string $class)
    {
        parent::__construct(sprintf('Invalid class: %s. The conversion class requires a constructor.', $class));
    }
}
