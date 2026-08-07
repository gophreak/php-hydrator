<?php

declare(strict_types=1);

namespace Tests\Unit\KeyResolvers;

use Hydrator\KeyResolvers\SnakeCaseKeyResolver;
use Hydrator\Sources\ArraySource;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \Hydrator\KeyResolvers\SnakeCaseKeyResolver
 */
final class SnakeCaseNamingResolverTest extends TestCase
{
    #[DataProvider('different_case_provider')]
    public function testResolve(ArraySource $source, string $input, string $expected): void
    {
        $this->assertSame($expected, new SnakeCaseKeyResolver()->resolve($source, $input));
    }

    public static function different_case_provider(): iterable
    {
        yield 'standard snake case' => [
            new ArraySource(['snake_case' => 'value']),
            'snakeCase',
            'snake_case',
        ];

        yield 'acronym snake case' => [
            new ArraySource(['test_id' => 'value']),
            'testID',
            'test_id',
        ];

        yield 'acronym snake case 2' => [
            new ArraySource(['ip_address' => 'value']),
            'IPAddress',
            'ip_address',
        ];

        yield 'single word' => [
            new ArraySource(['test' => 'value']),
            'test',
            'test',
        ];

        yield 'already snake case' => [
            new ArraySource(['snake_case' => 'value']),
            'snake_case',
            'snake_case',
        ];
    }
}
