<?php

namespace Bdf\Prime\Record;

use Bdf\Prime\Platform\PlatformInterface;
use Bdf\Prime\Query\Expression\ExpressionInterface;
use InvalidArgumentException;
use ReflectionClass;

use function sprintf;

/**
 * Handle a single record class instantiation
 *
 * @template R as object
 * @internal
 */
final class RecordInstantiator
{
    /**
     * @var array<string, string|ExpressionInterface>|null
     */
    private ?array $projection = null;

    public function __construct(
        /**
         * @var class-string<R>
         */
        public readonly string $recordClass,

        /**
         * @var array<string, Field>
         */
        public readonly array $fields,
    ) {
    }

    public function projection(): array
    {
        if (($projection = $this->projection) !== null) {
            return $projection;
        }

        $projection = [];

        foreach ($this->fields as $name => $field) {
            if ($field->projection === false) {
                continue;
            }

            $name = $field->projection ?? $field->name ?? $name;

            if ($field->expression) {
                $projection[$name] = $field->expression;
            } else {
                $projection[] = $name;
            }
        }

        return $projection;
    }

    /**
     * @param array $data
     * @param PlatformInterface $platform
     * @return R
     */
    public function instantiate(array $data, PlatformInterface $platform): object
    {
        $types = $platform->types();
        $constructorParameters = [];

        foreach ($this->fields as $name => $field) {
            $value = $data[$field->name ?? $name] ?? null;

            if ($field->type !== null) {
                $value = $types->fromDatabase($value, $field->type);
            }

            $constructorParameters[] = $field->cast($value);
        }

        $recordClass = $this->recordClass;
        return new $recordClass(...$constructorParameters);
    }

    /**
     * @param class-string<T> $recordClass
     * @return self<T>
     * @template T as object
     */
    public static function fromRecordClass(string $recordClass): self
    {
        $reflectionParameters = (new ReflectionClass($recordClass))->getConstructor()?->getParameters();

        if ($reflectionParameters === null) {
            throw new InvalidArgumentException(sprintf('The record class %s must have a constructor', $recordClass));
        }

        $parameters = [];

        foreach ($reflectionParameters as $parameter) {
            $parameters[$parameter->getName()] = Field::fromReflectionParameter($parameter);
        }

        return new self($recordClass, $parameters);
    }
}
