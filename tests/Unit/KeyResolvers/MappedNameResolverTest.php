<?php

declare(strict_types=1);

namespace Tests\Unit\KeyResolvers;

use Hydrator\KeyResolvers\MappedNameResolver;
use Hydrator\Sources\ArraySource;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \Hydrator\KeyResolvers\MappedNameResolver
 */
final class MappedNameResolverTest extends TestCase
{
    #[DataProvider('different_map_provider')]
    public function testResolve(ArraySource $source, array $map, string $input, string $expected): void
    {
        $this->assertSame($expected, new MappedNameResolver($map)->resolve($source, $input));
    }

    public static function different_map_provider(): iterable
    {
        yield 'case exists returns mapped' => [
            new ArraySource(['bar' => 'value']),
            ['foo' => 'bar'],
            'foo',
            'bar',
        ];

        yield 'case does not exist returns original' => [
            new ArraySource(['yang' => 'value']),
            ['ying' => 'yang'],
            'yang',
            'yang',
        ];
    }
}
