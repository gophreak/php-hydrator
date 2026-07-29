<?php

declare(strict_types=1);

namespace Tests\Unit\Strategies;

use Hydrator\Strategies\MappedNameStrategy;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class MappedNameStrategyTest extends TestCase
{
    #[DataProvider('different_map_provider')]
    public function testResolve(array $map, string $input, string $expected): void
    {
        $this->assertSame($expected, new MappedNameStrategy($map)->resolve($input));
    }

    public static function different_map_provider(): iterable
    {
        yield 'case exists returns mapped' => [
            ['foo' => 'bar'],
            'foo',
            'bar',
        ];

        yield 'case does not exist returns original' => [
            ['ying' => 'yang'],
            'yang',
            'yang',
        ];
    }
}
