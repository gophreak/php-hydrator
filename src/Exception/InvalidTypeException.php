<?php

declare(strict_types=1);

namespace Hydrator\Exception;

final class InvalidTypeException extends \RuntimeException
{
    public function __construct(string $expected, string $received)
    {
        parent::__construct(sprintf('Invalid type: expected %s, received %s.', $expected, $received));
    }
}
