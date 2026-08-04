# Hydrator

**Type-safe object hydration for PHP.**

Hydrator is a lightweight, framework-agnostic library for converting untyped input into strongly typed PHP objects.

It eliminates repetitive casting and manual mapping by automatically hydrating arrays and HTTP requests into immutable DTOs.

## Installation

```bash
composer require gophreak/php-hydrator
```

Requires PHP 8.4 or higher.

## Quick Start

### Request DTO

```php
final readonly class CreateUserRequest
{
    public function __construct(
        public int $id,
        public string $name,
        public bool $admin = false,
    ) {}
}
```

Hydration:

```php
$user = Hydrator::fromArray($data)
    ->to(CreateUserRequest::class);

$user->id;      // int
$user->name;    // string
$user->admin;   // bool
```

No manual casts.

No repeated assignments.

No string lookups throughout the application.

## Hydrating Specific Objects

Some classes cannot be hydrated recursively because they are represented by a scalar value rather than an array. DateTime is a common example—it is typically represented by a string.

```php
final readonly class User {
    public function __construct(
        public int $id,
        public string $firstName,
        public string $lastName,
        public string $email,
        public DateTime $createdAt,
    ) {}
}

$user = Hydrator::fromArray([
    'id' => '12345',
    'firstName' => 'John',
    'lastName' => 'Smith',
    'email' => 'john.smith@example.com',
    'createdAt' => '2023-01-01 00:00:00',
])->to(User::class);
```

By default, Hydrator recursively hydrates objects from arrays. DateTime is represented by a string instead, so you need to tell Hydrator how to construct it.

The solution is to use a class Factory registration which extends beyond just DateTime.

```php
$user = Hydrator::fromArray([
    'id' => '12345',
    'firstName' => 'John',
    'lastName' => 'Smith',
    'email' => 'john.smith@example.com',
    'createdAt' => '2023-01-01 00:00:00',
])
->withClassFactory(
    DateTime::class,
    fn (string $value) => new DateTime($value),
)
->to(User::class);
```

The registered factory tells Hydrator how to construct that specific type from the supplied value.

Class factories can be registered for any type, making them useful for value objects such as DateTime, UUIDs, Money objects, or custom domain types.

## The Problem

PHP applications constantly deal with untyped input.

Whether it comes from an HTTP request, JSON payload, configuration file, environment variable or message queue, application code is often littered with repetitive casts and assignments.

```php
$id = (int) $request->input('id');
$debug = filter_var(config('app.debug'), FILTER_VALIDATE_BOOL);
$timeout = (int) config('http.timeout');
```

This has several drawbacks:

* repetitive casting
* duplicated parsing logic
* string-typed configuration
* poor IDE support
* difficult testing

Hydrator aims to solve this problem once.

## Philosophy

Hydrator is built around three simple concepts:

### Sources

Where values come from.

Built-in sources:

* Arrays
* PSR-7 Requests
  
Other common inputs can be hydrated by first converting them to one of the built-in sources:

* Laravel/Symfony Requests (via PSR-7 bridges)
* Configuration and environment variables (via arrays)
* JSON payloads (via `json_decode()`)
* Message queue payloads (via array deserialization)

Every input source implements the same interface.

```php
interface Source
{
    public function has(string $key): bool;

    public function get(string $key): mixed;
}
```

---

### Conversion

How values are converted.

Built-in conversions:

* `string`
* `int`
* `float`
* `bool`

Object types are hydrated recursively.

---

### Hydration

How typed values become objects.

Instead of manually assigning properties:

```php
$this->id = (int) $request->input('id');
$this->name = $request->input('name');
$this->active = filter_var($request->input('active'), FILTER_VALIDATE_BOOL);
```

Hydrator performs the mapping automatically.

## Naming Strategies

Sometimes the input data keys don't match your property names (e.g., `snake_case` in JSON vs `camelCase` in PHP).

### Snake Case

Converts `snake_case` input keys to `camelCase` properties.

```php
final readonly class UserDto
{
    public function __construct(
        public string $firstName,
        public string $lastName,
    ) {}
}
```

```php
use Hydrator\Strategies\SnakeCaseNamingStrategy;

$user = Hydrator::fromArray([
    'first_name' => 'John',
    'last_name' => 'Smith',
])->using(new SnakeCaseNamingStrategy())
  ->to(UserDto::class);
```

### Explicit Mapping

Map specific input keys to property names.

```php
final readonly class UserDto
{
    public function __construct(
        public string $firstName,
        public string $lastName,
    ) {}
}
```

```php
use Hydrator\Strategies\MappedNameStrategy;

$user = Hydrator::fromArray([
    'given_name' => 'John',
    'family_name' => 'Smith',
])->using(new MappedNameStrategy([
    'firstName' => 'given_name',
    'lastName' => 'family_name',
]))->to(UserDto::class);
```

### Nested Objects

Hydrator automatically hydrates nested objects.

```php
final readonly class Address
{
    public function __construct(
        public string $street,
        public string $city,
    ) {}
}

final readonly class User
{
    public function __construct(
        public string $name,
        public Address $address,
    ) {}
}

$user = Hydrator::fromArray([
    'name' => 'Charles Dickens',
    'address' => [
        'street' => '123 Main St',
        'city' => 'Anytown',
    ],
])->to(User::class);

$user->name; // Charles Dickens
$user->address->street; // 123 Main St
$user->address->city; // Anytown
```

## Framework Agnostic

Hydrator has no framework dependencies.

Framework integrations can be built on top of the `Source` interface, allowing Laravel, Symfony and PSR-7 adapters without changing the core library.

## Features

* Strongly typed hydration
* Constructor-based hydration
* Nested object hydration
* Custom class factories
* Naming strategies
* Framework-agnostic
* Zero runtime dependencies

## Future Ideas

* Collection hydrators
* Built-in value object factories
* Attribute-based mapping
* Metadata caching
* Framework adapters

## Non-Goals

Hydrator is **not**:

* an ORM
* a serializer
* a validation framework
* a replacement for Laravel's Validator

Instead, it focuses on one responsibility:

> **Safely converting untyped input into strongly typed PHP objects.**

