<?php

declare(strict_types=1);

namespace Tests\Unit;

use Hydrator\Exception\InvalidTypeException;
use Hydrator\Exception\MissingValueException;
use Hydrator\Hydrator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tests\TestObjects\CastingObject;
use Tests\TestObjects\Person;
use Tests\TestObjects\PersonWithDefault;
use Tests\TestObjects\PersonWithNullable;

final class HydratorTest extends TestCase
{
    public function test_from_array(): void
    {
        $obj = Hydrator::fromArray([
            'name' => 'John Smith',
            'age' => 30,
            'email' => 'john.smith@example.com',
        ])->to(Person::class);

        $this->assertSame('John Smith', $obj->name);
        $this->assertSame(30, $obj->age);
        $this->assertSame('john.smith@example.com', $obj->email);
    }

    public static function data_casting_provider(): iterable
    {
        yield 'all correct' => [
            [
                'string' => 'John Smith',
                'int' => 30,
                'float' => 17.5,
                'boolean' => true,
                'object' => new \stdClass(),
            ], [
                'string' => 'John Smith',
                'int' => 30,
                'float' => 17.5,
                'boolean' => true,
                'object' => new \stdClass(),
            ],
        ];

        yield 'all correct alternative' => [
            [
                'string' => 'Different',
                'int' => 41,
                'float' => 19.3,
                'boolean' => false,
                'object' => new \stdClass(),
            ], [
                'string' => 'Different',
                'int' => 41,
                'float' => 19.3,
                'boolean' => false,
                'object' => new \stdClass(),
            ],
        ];

        yield 'int as string to int' => [
            [
                'string' => 'Different',
                'int' => '74',
                'float' => 19.3,
                'boolean' => false,
                'object' => new \stdClass(),
            ], [
                'string' => 'Different',
                'int' => 74,
                'float' => 19.3,
                'boolean' => false,
                'object' => new \stdClass(),
            ],
        ];

        yield 'float as string to float' => [
            [
                'string' => 'Different',
                'int' => 74,
                'float' => '569.3566',
                'boolean' => false,
                'object' => new \stdClass(),
            ], [
                'string' => 'Different',
                'int' => 74,
                'float' => 569.3566,
                'boolean' => false,
                'object' => new \stdClass(),
            ],
        ];

        yield 'bool as 0 to bool false' => [
            [
                'string' => 'Different',
                'int' => 74,
                'float' => '569.3566',
                'boolean' => 0,
                'object' => new \stdClass(),
            ], [
                'string' => 'Different',
                'int' => 74,
                'float' => 569.3566,
                'boolean' => false,
                'object' => new \stdClass(),
            ],
        ];

        yield 'bool as 1 to bool true' => [
            [
                'string' => 'Different',
                'int' => 74,
                'float' => '569.3566',
                'boolean' => 1,
                'object' => new \stdClass(),
            ], [
                'string' => 'Different',
                'int' => 74,
                'float' => 569.3566,
                'boolean' => true,
                'object' => new \stdClass(),
            ],
        ];

        yield 'bool as string "0" to bool false' => [
            [
                'string' => 'Different',
                'int' => 74,
                'float' => '569.3566',
                'boolean' => '0',
                'object' => new \stdClass(),
            ], [
                'string' => 'Different',
                'int' => 74,
                'float' => 569.3566,
                'boolean' => false,
                'object' => new \stdClass(),
            ],
        ];

        yield 'bool as string "1" to bool true' => [
            [
                'string' => 'Different',
                'int' => 74,
                'float' => '569.3566',
                'boolean' => '1',
                'object' => new \stdClass(),
            ], [
                'string' => 'Different',
                'int' => 74,
                'float' => 569.3566,
                'boolean' => true,
                'object' => new \stdClass(),
            ],
        ];

        yield 'bool as string "true" to bool true' => [
            [
                'string' => 'Different',
                'int' => 74,
                'float' => '569.3566',
                'boolean' => 'true',
                'object' => new \stdClass(),
            ], [
                'string' => 'Different',
                'int' => 74,
                'float' => 569.3566,
                'boolean' => true,
                'object' => new \stdClass(),
            ],
        ];

        yield 'bool as string "false" to bool false' => [
            [
                'string' => 'Different',
                'int' => 74,
                'float' => '569.3566',
                'boolean' => 'false',
                'object' => new \stdClass(),
            ], [
                'string' => 'Different',
                'int' => 74,
                'float' => 569.3566,
                'boolean' => false,
                'object' => new \stdClass(),
            ],
        ];
    }

    #[DataProvider('data_casting_provider')]
    public function test_from_array_with_casting_required(array $input, array $expected): void
    {
        $obj = Hydrator::fromArray($input)->to(CastingObject::class);

        $this->assertSame($expected['string'], $obj->string);
        $this->assertSame($expected['int'], $obj->int);
        $this->assertSame($expected['float'], $obj->float);
        $this->assertSame($expected['boolean'], $obj->boolean);
        $this->assertEquals($expected['object'], $obj->object);
    }

    public static function data_invalid_casting_provider(): iterable
    {
        yield 'string is array' => [
            [
                'string' => [],
                'int' => 74,
                'float' => 19.3,
                'boolean' => false,
                'object' => new \stdClass(),
            ], 'Invalid type: expected string, received array.',
        ];

        yield 'string is bool' => [
            [
                'string' => false,
                'int' => 74,
                'float' => 19.3,
                'boolean' => false,
                'object' => new \stdClass(),
            ], 'Invalid type: expected string, received boolean.',
        ];

        yield 'int is string' => [
            [
                'string' => 'Different',
                'int' => 'Hello',
                'float' => 19.3,
                'boolean' => true,
                'object' => new \stdClass(),
            ], 'Invalid type: expected integer, received string.',
        ];

        yield 'float is string' => [
            [
                'string' => 'Different',
                'int' => 13,
                'float' => 'Howdy 21',
                'boolean' => true,
                'object' => new \stdClass(),
            ], 'Invalid type: expected float, received string.',
        ];

        yield 'boolean int not 0 or 1' => [
            [
                'string' => 'Different',
                'int' => 74,
                'float' => 19.3,
                'boolean' => 2,
                'object' => new \stdClass(),
            ], 'Invalid type: expected bool, received integer.',
        ];
    }

    #[DataProvider('data_invalid_casting_provider')]
    public function test_from_array_with_invalid_casting_throws_exception(array $input, string $expected): void
    {
        $this->expectException(InvalidTypeException::class);
        $this->expectExceptionMessageIs($expected);

        Hydrator::fromArray($input)->to(CastingObject::class);
    }

    public function test_from_array_with_partial_data_throws_exception(): void
    {
        $this->expectException(MissingValueException::class);
        $this->expectExceptionMessageIs('Missing value for property "age"');

        Hydrator::fromArray([
            'name' => 'John Smith',
        ])->to(Person::class);
    }

    public function test_from_array_with_partial_nullable_data_succeeds(): void
    {
        $obj = Hydrator::fromArray([
            'name' => 'John Smith',
        ])->to(PersonWithNullable::class);

        $this->assertSame('John Smith', $obj->name);
        $this->assertNull($obj->age);
        $this->assertNull($obj->email);
    }

    public function test_from_array_with_partial_default_data_succeeds(): void
    {
        $obj = Hydrator::fromArray([
            'name' => 'John Smith',
        ])->to(PersonWithDefault::class);

        $this->assertSame('John Smith', $obj->name);
        $this->assertSame(21, $obj->age);
        $this->assertSame('anonymous@example.com', $obj->email);
    }
}
