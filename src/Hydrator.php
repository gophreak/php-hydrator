<?php

declare(strict_types=1);

namespace Hydrator;

use Hydrator\Exception\InvalidClassException;
use Hydrator\Exception\InvalidTypeException;
use Hydrator\Exception\MissingValueException;
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
    public function withClassFactory(
        string $class,
        callable $factory,
    ): self {
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
            $name = $parameter->getName();

            $paramType = $parameter->getType();
            if (!$paramType instanceof \ReflectionNamedType) {
                throw new InvalidClassException(sprintf(
                    'Invalid class: %s. The conversion class constructor arguments must be strictly typed.',
                    $class,
                ));
            }

            if ($this->source->has($name)) {
                $value = $this->source->get($name);
            } elseif ($this->strategy !== null && $this->source->has($this->strategy->resolve($name))) {
                $value = $this->source->get($this->strategy->resolve($name));
            } elseif ($parameter->isDefaultValueAvailable()) {
                $args[] = $parameter->getDefaultValue();

                continue;
            } elseif ($parameter->allowsNull()) {
                $args[] = null;

                continue;
            } else {
                throw new MissingValueException(sprintf('Missing value for property "%s".', $name));
            }

            $paramTypeName = $paramType->getName();

            if (class_exists($paramTypeName) && !enum_exists($paramTypeName)) {
                $args[] = $this->hydrateObject($paramTypeName, $value);

                continue;
            }

            $args[] = $this->cast($value, $paramType);
        }

        return $reflection->newInstanceArgs($args);
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
