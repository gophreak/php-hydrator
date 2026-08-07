<?php

declare(strict_types=1);

namespace Tests\Unit\Strategies;

use Hydrator\Sources\ArraySource;
use Hydrator\Strategies\MappedNameStrategy;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \Hydrator\Strategies\MappedNameStrategy
 */
final class MappedNameStrategyTest extends TestCase
{
    #[DataProvider('different_map_provider')]
    public function testResolve(ArraySource $source, array $map, string $input, string $expected): void
    {
        $this->assertSame($expected, new MappedNameStrategy($map)->resolve($source, $input));
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
