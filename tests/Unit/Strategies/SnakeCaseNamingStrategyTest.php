<?php

declare(strict_types=1);

namespace Tests\Unit\Strategies;

use Hydrator\Strategies\SnakeCaseNamingStrategy;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SnakeCaseNamingStrategyTest extends TestCase
{
    public static function different_case_provider(): iterable
    {
        yield 'standard snake case' => [
            'snakeCase',
            'snake_case',
        ];

        yield 'acronym snake case' => [
            'testID',
            'test_id',
        ];

        yield 'acronym snake case 2' => [
            'IPAddress',
            'ip_address',
        ];

        yield 'single word' => [
            'test',
            'test',
        ];

        yield 'already snake case' => [
            'snake_case',
            'snake_case',
        ];
    }

    #[DataProvider('different_case_provider')]
    public function test_resolve(string $input, string $expected): void
    {
        $this->assertSame($expected, new SnakeCaseNamingStrategy()->resolve($input));
    }
}
