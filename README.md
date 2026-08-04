# Hydrator

**Type-safe object hydration for PHP.**

Hydrator is a lightweight, framework-agnostic library that converts untyped input into strongly typed PHP objects.

Instead of manually reading input and scattering casts throughout your application, Hydrator converts untyped data into strongly typed, immutable PHP objects.

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

Examples:

* HTTP Request
* PSR-7 Requests
* Arrays
* Environment Variables
* JSON (via `fromArray` after `json_decode`)

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
* `enums`

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

## Example

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
* Nested object hydration
* Constructor-based hydration
* Immutable DTO support
* Naming strategies
* Framework-agnostic
* Zero runtime dependencies

## Future Ideas

* Collection hydrators
* Custom type casters
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

## Installation

```bash
composer require gophreak/php-hydrator
```