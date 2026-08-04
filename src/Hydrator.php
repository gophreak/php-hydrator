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

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            new ArraySource($data),
        );
    }

    public static function fromPsrRequest(ServerRequestInterface $request, $options = PsrRequestSource::PARSE_DEFAULT): self
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
            $value = $this->source->get($name);
            $paramType = $parameter->getType();
            if (!$paramType instanceof \ReflectionNamedType) {
                throw new InvalidClassException(sprintf('Invalid class: %s. The conversion class constructor arguments must be strictly typed.', $class));
            }

            if (class_exists($paramType->getName()) && !enum_exists($paramType->getName())) {
                if (!is_array($value)) {
                    throw new InvalidTypeException('array', gettype($value));
                }

                /** @var array<string, mixed> $arrayData */
                $arrayData = $value;
                $value = Hydrator::fromArray($arrayData)->using($this->strategy)->to($paramType->getName());
            } elseif ($this->source->has($name)) {
                $value = $this->cast($value, $paramType);
            } elseif ($this->strategy !== null && $this->source->has($this->strategy->resolve($name))) {
                $value = $this->cast($this->source->get($this->strategy->resolve($name)), $paramType);
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
