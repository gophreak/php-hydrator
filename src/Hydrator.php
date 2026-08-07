<?php

declare(strict_types=1);

namespace Hydrator;

use Hydrator\Exception\InvalidClassException;
use Hydrator\Exception\InvalidTypeException;
use Hydrator\Exception\MissingValueException;
use Hydrator\Exception\UnsupportedParameterTypeException;
use Hydrator\Sources\ArraySource;
use Hydrator\Sources\PsrRequestSource;
use Hydrator\Strategies\NamingStrategy;
use Psr\Http\Message\ServerRequestInterface;

final class Hydrator
{
    private ?NamingStrategy $strategy = null;

    /** @var array<class-string, callable(mixed): object> */
    private array $classFactories = [];

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            new ArraySource($data),
        );
    }

    public static function fromPsrRequest(ServerRequestInterface $request, int $options = PsrRequestSource::PARSE_DEFAULT): self
    {
        return new self(
            new PsrRequestSource($request, $options),
        );
    }

    public function __construct(
        private readonly Source $source,
    ) {}

    public function using(?NamingStrategy $strategy): self
    {
        $this->strategy = $strategy;

        return $this;
    }

    /**
     * @param class-string $class
     */
    public function withClassFactory(string $class, callable $factory): self {
        $this->classFactories[$class] = $factory;

        return $this;
    }

    /**
     * @param array<class-string, callable(mixed): object> $factories
     */
    public function withClassFactories(array $factories): self
    {
        $this->classFactories = array_merge($this->classFactories, $factories);

        return $this;
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return T
     *
     * @throws MissingValueException
     * @throws \ReflectionException
     */
    public function to(string $class): object
    {
        $reflection = new \ReflectionClass($class);

        $constructor = $reflection->getConstructor();
        if ($constructor === null) {
            throw new InvalidClassException(sprintf('Invalid class: %s. The conversion class requires a constructor.', $class));
        }

        $args = [];

        foreach ($constructor->getParameters() as $parameter) {
            try {
                $args[] = $this->resolveArgument($parameter);
            } catch (UnsupportedParameterTypeException $e) {
                throw new InvalidClassException(
                    message: sprintf(
                        'Invalid class: %s. The conversion class constructor arguments must be strictly typed.',
                        $class,
                    ),
                    previous: $e,
                );
            }
        }

        return $reflection->newInstanceArgs($args);
    }

    /**
     * @throws \ReflectionException
     * @throws UnsupportedParameterTypeException
     */
    private function resolveArgument(\ReflectionParameter $parameter): mixed
    {
        $paramType = $parameter->getType();
        if (!$paramType instanceof \ReflectionNamedType && ! $paramType instanceof \ReflectionUnionType) {
            throw new UnsupportedParameterTypeException('Only named parameters are supported.');
        }

        $paramName = $parameter->getName();
        if ($this->source->has($paramName)) {
            $value = $this->source->get($paramName);
        } elseif ($this->strategy !== null && $this->source->has($this->strategy->resolve($paramName))) {
            $value = $this->source->get($this->strategy->resolve($paramName));
        } elseif ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        } elseif ($parameter->allowsNull()) {
            return null; // design decision, we can set nullable value to null when not present in source.
        } else {
            throw new MissingValueException(sprintf('Missing value for property "%s".', $paramName));
        }

        if ($paramType instanceof \ReflectionUnionType) {
            $paramTypeTypes = $paramType->getTypes();

            // Pass 1 - resolve any object type if we can
            foreach ($paramTypeTypes as $paramTypeUnion) {
                $typeName = $paramTypeUnion->getName();

                if (!class_exists($typeName) || enum_exists($typeName)) {
                    continue;
                }

                try {
                    return $this->resolveNamedType($value, $paramTypeUnion);
                } catch (InvalidTypeException) {
                    // Try the next object type.
                }
            }

            // Pass 2 - resolve any scalar type if we can
            if (array_any(
                $paramTypeTypes,
                fn ($paramTypeUnion) => match ($paramTypeUnion->getName()) {
                    'string' => is_string($value),
                    'int', 'integer' => is_int($value),
                    'float' => is_float($value),
                    'bool', 'boolean' => is_bool($value),
                    'array' => is_array($value),
                    default => $value instanceof ($paramTypeUnion->getName()),
                },
            )) {
                return $value;
            }

            // Pass 3 - cast any values we can
            foreach ($paramTypeTypes as $paramTypeUnion) {
                try {
                    return $this->resolveNamedType($value, $paramTypeUnion->getName());
                } catch (InvalidTypeException) {
                    // Try the next union member.
                }
            }

            throw new InvalidTypeException(
                expected: implode('|', array_map(
                    static fn (\ReflectionNamedType $type) => $type->getName(),
                    $paramTypeTypes,
                )),
                received: gettype($value),
            );
        }

        return $this->resolveNamedType($value, $paramType);
    }

    /**
     * @throws \ReflectionException
     */
    private function resolveNamedType(mixed $value, \ReflectionNamedType $paramType): mixed
    {
        $paramTypeName = $paramType->getName();
        if (class_exists($paramTypeName) && !enum_exists($paramTypeName)) {
            return $this->hydrateObject($paramTypeName, $value);
        }

        return $this->cast($value, $paramType);
    }

    /**
     * @param class-string $paramTypeName
     * @throws \ReflectionException
     */
    private function hydrateObject(string $paramTypeName, mixed $value): object
    {
        if ($value instanceof $paramTypeName) {
            /** @var object $value */
            return $value;
        }

        if (isset($this->classFactories[$paramTypeName])) {
            return ($this->classFactories[$paramTypeName])($value);
        }

        if (!is_array($value)) {
            throw new InvalidTypeException('array', gettype($value));
        }

        /** @var array<string, mixed> $arrayData */
        $arrayData = $value;

        return Hydrator::fromArray($arrayData)
            ->using($this->strategy)
            ->withClassFactories($this->classFactories)
            ->to($paramTypeName)
        ;
    }

    private function cast(
        mixed $value,
        \ReflectionNamedType $type,
    ): mixed {
        switch ($type->getName()) {
            case 'string':
                if (!is_string($value) && !is_numeric($value)) {
                    throw new InvalidTypeException(
                        expected: 'string',
                        received: gettype($value),
                    );
                }

                return (string) $value;

            case 'int':
            case 'integer':
                $val = filter_var($value, FILTER_VALIDATE_INT);
                if ($val === false) {
                    throw new InvalidTypeException(
                        expected: 'integer',
                        received: gettype($value),
                    );
                }

                return intval($val);

            case 'float':
                if (!is_numeric($value)) {
                    throw new InvalidTypeException(
                        expected: 'float',
                        received: gettype($value),
                    );
                }

                return (float) $value;

            case 'bool':
                $result = filter_var(
                    $value,
                    FILTER_VALIDATE_BOOLEAN,
                    FILTER_NULL_ON_FAILURE
                );

                if ($result === null) {
                    throw new InvalidTypeException(
                        expected: 'bool',
                        received: gettype($value),
                    );
                }

                return $result;
        }

        return $value;
    }
}
