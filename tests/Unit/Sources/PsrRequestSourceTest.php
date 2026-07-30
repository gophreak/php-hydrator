<?php

declare(strict_types=1);

namespace Tests\Unit\Sources;

use Hydrator\Sources\PsrRequestSource;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class PsrRequestSourceTest extends TestCase
{
    public function testHasReturnsCorrectlyForParsedBody(): void
    {
        $request = new ServerRequest('POST', '/')
            ->withParsedBody([
                'foo' => 'bar',
                'baz' => 'qux',
                'quux' => 'corge',
            ])
        ;

        $source = new PsrRequestSource($request);

        $this->assertTrue($source->has('foo'));
        $this->assertTrue($source->has('baz'));
        $this->assertTrue($source->has('quux'));
        $this->assertFalse($source->has('missing'));
    }

    public function testGetReturnsCorrectlyFromParsedBody(): void
    {
        $request = new ServerRequest('POST', '/')
            ->withParsedBody([
                'foo' => 'bar',
                'baz' => 'qux',
                'quux' => 'corge',
            ])
        ;

        $source = new PsrRequestSource($request);

        $this->assertSame('bar', $source->get('foo'));
        $this->assertSame('qux', $source->get('baz'));
        $this->assertSame('corge', $source->get('quux'));
        $this->assertNull($source->get('missing'));
    }

    public function testReturnsQueryParameters(): void
    {
        $request = new ServerRequest('GET', '/')
            ->withQueryParams([
                'page' => '2',
                'sort' => 'name',
            ])
        ;

        $source = new PsrRequestSource($request);

        $this->assertTrue($source->has('page'));
        $this->assertSame('2', $source->get('page'));
        $this->assertSame('name', $source->get('sort'));
    }

    public function testReturnsAttributes(): void
    {
        $request = new ServerRequest('GET', '/')
            ->withAttribute('userId', 123)
            ->withAttribute('tenant', 'acme')
        ;

        $source = new PsrRequestSource($request);

        $this->assertTrue($source->has('userId'));
        $this->assertSame(123, $source->get('userId'));
        $this->assertSame('acme', $source->get('tenant'));
    }

    public function testParsedBodyTakesPrecedenceOverQueryParameters(): void
    {
        $request = new ServerRequest('POST', '/')
            ->withParsedBody([
                'foo' => 'body',
            ])
            ->withQueryParams([
                'foo' => 'query',
            ])
        ;

        $source = new PsrRequestSource($request);

        $this->assertSame('body', $source->get('foo'));
    }

    public function testQueryParametersTakePrecedenceOverAttributes(): void
    {
        $request = new ServerRequest('GET', '/')
            ->withQueryParams([
                'foo' => 'query',
            ])
            ->withAttribute('foo', 'attribute')
        ;

        $source = new PsrRequestSource($request);

        $this->assertSame('query', $source->get('foo'));
    }

    public function testNullValueStillExists(): void
    {
        $request = new ServerRequest('POST', '/')
            ->withParsedBody([
                'middleName' => null,
            ])
        ;

        $source = new PsrRequestSource($request);

        $this->assertTrue($source->has('middleName'));
        $this->assertNull($source->get('middleName'));
    }
}
