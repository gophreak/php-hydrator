# Hydrator

**Type-safe object hydration for PHP.**

Hydrator is a framework-agnostic library for converting untyped input into strongly typed PHP objects.

Instead of manually reading arrays, request parameters, configuration values, or environment variables and casting them throughout your application, Hydrator provides a consistent API for loading, validating, converting, and hydrating data into immutable objects.

## The Problem

PHP applications receive data from many different sources:

* HTTP requests
* Configuration
* Environment variables
* JSON payloads
* CLI arguments
* Message queues
* Arrays

Every source exposes values as `mixed` or `string`, leaving developers to repeatedly write code such as:

```php
$id = (int) $request->input('id');
$debug = filter_var(config('app.debug'), FILTER_VALIDATE_BOOL);
$timeout = (int) config('http.timeout');
```

This has several drawbacks:

* repetitive casting
* duplicated parsing logic
* inconsistent validation
* string-typed configuration
* poor IDE support
* difficult testing

Hydrator aims to solve this problem once.

## Philosophy

Hydrator separates four concerns:

### Sources

Where values come from.

Examples:

* HTTP Request
* Configuration
* Environment Variables
* Arrays
* JSON
* Headers
* Query Parameters
* PSR-7 Requests

Every source implements a common interface.

```php
interface Source
{
    public function has(string $key): bool;

    public function get(string $key): mixed;
}
```

---

### Types

How values are converted.

Built-in types include:

* string
* int
* float
* bool
* enum
* UUID
* URL
* email
* JSON
* bytes
* duration
* date/time

Example:

```php
$input->int('id')->value();
```

---

### Validators

How converted values are constrained.

```php
$input
    ->int('port')
    ->between(1, 65535)
    ->value();
```

Validators operate on typed values rather than raw strings.

---

### Mappers

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
$request = Hydrator::from(new RequestSource($request))
    ->into(CreateUserRequest::class);
```

No manual casts.

No repeated assignments.

No string lookups throughout the application.

## Other Sources

Exactly the same API works for different inputs.

Configuration:

```php
$config = Hydrator::from(
    new ConfigSource($repository)
);

$mail = $config->into(MailConfiguration::class);
```

Environment:

```php
$env = Hydrator::from(
    new EnvironmentSource()
);

$app = $env->into(ApplicationConfiguration::class);
```

Arrays:

```php
$user = Hydrator::fromArray($payload)
    ->into(UserDto::class);
```

## Framework Adapters

The core package contains no framework dependencies.

Separate adapters provide integration with popular frameworks.

### Laravel

```php
$user = Hydrator::laravel($request)
    ->into(CreateUserRequest::class);
```

### Symfony

```php
$user = Hydrator::symfony($request)
    ->into(CreateUserRequest::class);
```

### PSR-7

```php
$user = Hydrator::psr($request)
    ->into(CreateUserRequest::class);
```

## Goals

* Framework-agnostic
* Zero required dependencies
* Immutable DTO support
* Strong typing
* Extensible type system
* Pluggable validators
* Pluggable sources
* Excellent error messages
* Reflection cached for production performance

## Future Ideas

* Attribute-based property mapping
* Nested object hydration
* Collection support
* Automatic enum resolution
* Custom type registration
* Generated metadata cache
* IDE helper generation
* Laravel service provider
* Symfony bundle
* CLI argument hydration
* Message queue hydration

## Non-Goals

Hydrator is **not**:

* an ORM
* a serializer
* a validation framework
* a replacement for Laravel's Validator

Instead, it focuses on one responsibility:

> **Safely converting untyped input into strongly typed PHP objects.**
