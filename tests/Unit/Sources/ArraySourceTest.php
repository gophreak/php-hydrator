<?php

declare(strict_types=1);

namespace Tests\Unit\Sources;

use Hydrator\Sources\ArraySource;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \Hydrator\Sources\ArraySource
 */
final class ArraySourceTest extends TestCase
{
    public function testHasReturnsCorrectly(): void
    {
        $arr = new ArraySource(['foo' => 'bar', 'baz' => 'qux', 'quux' => 'corge']);

        $this->assertTrue($arr->has('foo'), 'foo should exist');
        $this->assertTrue($arr->has('baz'), 'baz should exist');
        $this->assertTrue($arr->has('quux'), 'quux should exist');
        $this->assertFalse($arr->has('f'), 'f should not exist');
    }

    public function testGetReturnsCorrectly(): void
    {
        $arr = new ArraySource(['foo' => 'bar', 'baz' => 'qux', 'quux' => 'corge']);

        $this->assertSame('bar', $arr->get('foo'));
        $this->assertSame('qux', $arr->get('baz'));
        $this->assertSame('corge', $arr->get('quux'));
        $this->assertNull($arr->get('f'), 'f should return null');
    }
}
