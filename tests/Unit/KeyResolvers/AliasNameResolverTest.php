<?php

declare(strict_types=1);

namespace Tests\Unit\KeyResolvers;

use Hydrator\KeyResolvers\AliasNameResolver;
use Hydrator\Sources\ArraySource;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \Hydrator\KeyResolvers\AliasNameResolver
 */
final class AliasNameResolverTest extends TestCase
{
    #[DataProvider('different_map_provider')]
    public function testResolve(ArraySource $source, array $map, string $input, string $expected): void
    {
        $this->assertSame($expected, new AliasNameResolver($map)->resolve($source, $input));
    }

    public static function different_map_provider(): iterable
    {
        yield 'case exists returns mapped from single' => [
            new ArraySource(['bar' => 'value']),
            ['foo' => ['bar']],
            'foo',
            'bar',
        ];

        yield 'case finds second' => [
            new ArraySource(['first_name' => 'value']),
            ['firstName' => ['firstname', 'first_name']],
            'first_name',
            'first_name',
        ];

        yield 'case finds third' => [
            new ArraySource(['first_name' => 'value']),
            ['firstName' => ['firstname', 'given_name', 'first_name']],
            'first_name',
            'first_name',
        ];

        yield 'case does not exist returns original' => [
            new ArraySource(['yang' => 'value']),
            ['ying' => ['yang']],
            'yang',
            'yang',
        ];
    }
}
