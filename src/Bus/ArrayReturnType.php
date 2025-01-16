<?php

namespace Bdf\Prime\Bus;

/**
 * Represents a return type of array of entities
 *
 * @template T as object
 * @implements ReturnTypeInterface<array<T>>
 */
final class ArrayReturnType implements ReturnTypeInterface
{
    public function __construct(
        /**
         * The entity class name
         *
         * @var class-string<T>
         */
        public readonly string $class,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function unwrappedType(): ?string
    {
        return $this->class;
    }

    /**
     * {@inheritdoc}
     */
    public function cast($value): array
    {
        return (array) $value;
    }

    /**
     * Create an array return type for the given entity class
     *
     * @param class-string<R> $class The entity class name
     * @return self<R>
     *
     * @template R as object
     */
    public static function of(string $class): self
    {
        return new self($class);
    }
}
