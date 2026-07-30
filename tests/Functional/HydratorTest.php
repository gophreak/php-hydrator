<?php

declare(strict_types=1);

namespace Tests\Functional;

use Hydrator\Exception\InvalidClassException;
use Hydrator\Exception\InvalidTypeException;
use Hydrator\Exception\MissingValueException;
use Hydrator\Hydrator;
use Hydrator\Sources\PsrRequestSource;
use Hydrator\Strategies\MappedNameStrategy;
use Hydrator\Strategies\SnakeCaseNamingStrategy;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Tests\TestObjects\Address;
use Tests\TestObjects\CastingObject;
use Tests\TestObjects\ClassWithNoType;
use Tests\TestObjects\ClassWithUnionType;
use Tests\TestObjects\Person;
use Tests\TestObjects\PersonSeparateName;
use Tests\TestObjects\PersonWithAddress;
use Tests\TestObjects\PersonWithDefault;
use Tests\TestObjects\PersonWithNullable;

/**
 * @internal
 *
 * @coversNothing
 */
final class HydratorTest extends TestCase
{
    #[DataProvider('data_provider')]
    public function testFromArray(array $input, array $expected): void
    {
        $obj = Hydrator::fromArray($input)->to(CastingObject::class);

        $this->assertSame($expected['string'], $obj->string);
        $this->assertSame($expected['int'], $obj->int);
        $this->assertSame($expected['float'], $obj->float);
        $this->assertSame($expected['boolean'], $obj->boolean);
        $this->assertEquals($expected['object'], $obj->object);
    }

    public static function data_provider(): iterable
    {
        yield 'no casting' => [
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

        yield 'no casting alternative' => [
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

    #[DataProvider('data_invalid_casting_provider')]
    public function testFromArrayWithInvalidCastingThrowsException(array $input, string $expected): void
    {
        $this->expectException(InvalidTypeException::class);
        $this->expectExceptionMessageIs($expected);

        Hydrator::fromArray($input)->to(CastingObject::class);
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

    public function testFromArrayWithPartialDataThrowsException(): void
    {
        $this->expectException(MissingValueException::class);
        $this->expectExceptionMessageIs('Missing value for property "age"');

        Hydrator::fromArray([
            'name' => 'John Smith',
        ])->to(Person::class);
    }

    public function testFromArrayWithPartialNullableDataSucceeds(): void
    {
        $obj = Hydrator::fromArray([
            'name' => 'John Smith',
        ])->to(PersonWithNullable::class);

        $this->assertSame('John Smith', $obj->name);
        $this->assertNull($obj->age);
        $this->assertNull($obj->email);
    }

    public function testFromArrayWithPartialDefaultDataSucceeds(): void
    {
        $obj = Hydrator::fromArray([
            'name' => 'John Smith',
        ])->to(PersonWithDefault::class);

        $this->assertSame('John Smith', $obj->name);
        $this->assertSame(21, $obj->age);
        $this->assertSame('anonymous@example.com', $obj->email);
    }

    public function testWithSnakeCaseStrategyPasses(): void
    {
        $obj = Hydrator::fromArray([
            'first_name' => 'John',
            'middle_name' => 'Michael',
            'last_name' => 'Smith',
        ])->using(new SnakeCaseNamingStrategy())
            ->to(PersonSeparateName::class)
        ;

        $this->assertSame('John', $obj->firstName);
        $this->assertSame('Michael', $obj->middleName);
        $this->assertSame('Smith', $obj->lastName);
    }

    public function testWithMappedNameStrategyPasses(): void
    {
        $obj = Hydrator::fromArray([
            'given_name' => 'John',
            'middle_name' => 'Michael',
            'family_name' => 'Smith',
        ])->using(new MappedNameStrategy([
            'firstName' => 'given_name',
            'middleName' => 'middle_name',
            'lastName' => 'family_name',
        ]))
            ->to(PersonSeparateName::class)
        ;

        $this->assertSame('John', $obj->firstName);
        $this->assertSame('Michael', $obj->middleName);
        $this->assertSame('Smith', $obj->lastName);
    }

    public function testWithoutStrategyFails(): void
    {
        $this->expectException(MissingValueException::class);
        $this->expectExceptionMessageIs('Missing value for property "firstName"');

        Hydrator::fromArray([
            'first_name' => 'John',
            'middle_name' => 'Michael',
            'last_name' => 'Smith',
        ])->to(PersonSeparateName::class);
    }

    public function testNestedHydratorWorks(): void
    {
        $obj = Hydrator::fromArray([
            'name' => 'John Smith',
            'address' => [
                'street' => '123 Main St',
                'city' => 'Anytown',
                'province' => 'ON',
                'postcode' => 'A1B 2C3',
            ],
        ])->to(PersonWithAddress::class);

        $this->assertSame('John Smith', $obj->name);
        $this->assertInstanceOf(Address::class, $obj->address);

        $this->assertSame('123 Main St', $obj->address->street);
        $this->assertSame('Anytown', $obj->address->city);
        $this->assertSame('ON', $obj->address->province);
        $this->assertSame('A1B 2C3', $obj->address->postcode);
    }

    public function testPsrRequestSourceWorks(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->atLeast(2))
            ->method('getParsedBody')
            ->willReturn([
                'name' => 'John Smith',
                'address' => [
                    'street' => '123 Main St',
                    'city' => 'Anytown',
                    'province' => 'ON',
                    'postcode' => 'A1B 2C3',
                ]
            ]);
        $obj = Hydrator::fromPsrRequest($request)->to(PersonWithAddress::class);

        $this->assertSame('John Smith', $obj->name);
        $this->assertInstanceOf(Address::class, $obj->address);

        $this->assertSame('123 Main St', $obj->address->street);
        $this->assertSame('Anytown', $obj->address->city);
        $this->assertSame('ON', $obj->address->province);
        $this->assertSame('A1B 2C3', $obj->address->postcode);
    }

    public function testPsrRequestSourcePrioritisesBodyOverQuery(): void
    {
        $request = new ServerRequest('POST', '/?name=Robert%20Jackson&age=30&email=robert%40jackson.com')
            ->withParsedBody([
                'name' => 'John Smith',
                'age' => 18,
                'email' => 'john@smith.com',
            ])
        ;
        $obj = Hydrator::fromPsrRequest($request)->to(Person::class);

        $this->assertSame('John Smith', $obj->name);
        $this->assertSame(18, $obj->age);
        $this->assertSame('john@smith.com', $obj->email);
    }

    public function testPsrRequestSourceWorksWhenOnlyAllowingQuery(): void
    {
        $request = new ServerRequest('POST', '/?name=Robert%20Jackson&age=30&email=robert%40jackson.com')
            ->withParsedBody([
                'name' => 'John Smith',
                'age' => 18,
                'email' => 'john@smith.com',
            ])
        ;
        $obj = Hydrator::fromPsrRequest($request, PsrRequestSource::PARSE_QUERY)->to(Person::class);

        $this->assertSame('Robert Jackson', $obj->name);
        $this->assertSame(30, $obj->age);
        $this->assertSame('robert@jackson.com', $obj->email);
    }

    public function testHydratorThrowsExceptionWhenSourceHasNoConstructor(): void
    {
        $this->expectException(InvalidClassException::class);
        $this->expectExceptionMessageIs('Invalid class: stdClass. The conversion class requires a constructor.');

        Hydrator::fromArray([])->to(\stdClass::class);
    }

    public function testHydratorThrowsExceptionWhenSourceFieldIsNotCorrectDataType(): void
    {
        $this->expectException(InvalidTypeException::class);
        $this->expectExceptionMessageIs('Invalid type: expected array, received string.');

        $obj = Hydrator::fromArray([
            'name' => 'John Smith',
            'address' => 't',
        ])->to(PersonWithAddress::class);
    }

    public function testHydratorThrowsExceptionWithUndefinedDataType(): void
    {
        $this->expectException(InvalidClassException::class);
        $this->expectExceptionMessageIs('Invalid class: Tests\TestObjects\ClassWithNoType. The conversion class constructor arguments must be strictly typed.');
        $obj = Hydrator::fromArray([
            'name' => 'John Smith',
            'mixed' => 't',
        ])->to(ClassWithNoType::class);
    }

    public function testHydratorThrowsExceptionWithUnionDataType(): void
    {
        $this->expectException(InvalidClassException::class);
        $this->expectExceptionMessageIs('Invalid class: Tests\TestObjects\ClassWithUnionType. The conversion class constructor arguments must be strictly typed.');
        $obj = Hydrator::fromArray([
            'name' => 'John Smith',
            'mixed' => 't',
        ])->to(ClassWithUnionType::class);
    }
}
