<?php

declare(strict_types=1);

namespace Hydrator;

use Hydrator\Exception\InvalidTypeException;
use Hydrator\Exception\MissingValueException;
use Hydrator\Sources\ArraySource;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use ReflectionType;

final readonly class Hydrator
{
    public static function fromArray(array $data): self
    {
        return new self(
            new ArraySource($data),
        );
    }

    public function __construct(
        private Source $source,
    ) {}

    /**
     * @template T of object
     *
     * @param  class-string<T>  $class
     * @return T
     *
     * @throws MissingValueException
     * @throws ReflectionException
     */
    public function to(string $class): object
    {
        $reflection = new ReflectionClass($class);

        $constructor = $reflection->getConstructor();

        $args = [];

        foreach ($constructor->getParameters() as $parameter) {
            $name = $parameter->getName();
            if ($this->source->has($name)) {
                $value = $this->cast($this->source->get($name), $parameter->getType());
            } elseif ($parameter->isDefaultValueAvailable()) {
                $value = $parameter->getDefaultValue();
            } elseif ($parameter->allowsNull()) {
                $value = null;
            } else {
                throw new MissingValueException(sprintf('Missing value for property "%s"', $name));
            }

            $args[] = $value;
        }

        return $reflection->newInstanceArgs($args);
    }

    private function cast(
        mixed $value,
        ReflectionNamedType $type,
    ): mixed {
        switch ($type->getName()) {
            case 'string':
                if (! is_string($value) && ! is_numeric($value)) {
                    throw new InvalidTypeException(
                        expected: 'string',
                        received: gettype($value),
                    );
                }

                return (string) $value;
            case 'int':
            case 'integer':
                if (filter_var($value, FILTER_VALIDATE_INT) === false) {
                    throw new InvalidTypeException(
                        expected: 'integer',
                        received: gettype($value),
                    );
                }

                return (int) $value;
            case 'float':
                if (! is_numeric($value)) {
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
