<?php

namespace Bdf\Prime\Bus\Query\ReturnType;

use Bdf\Prime\Query\Pagination\PaginatorInterface;
use TypeError;

/**
 * Expected return type for paginator
 *
 * Usage:
 * ```php
 * #[PrimeQuery(MyEntity::class, method: QueryExecutionMethod::Paginate)]
 * class MyQuery
 * {
 *     // ...
 * }
 *
 * $results = $bus->query(new MyQuery(...), PaginatorType::of(MyEntity::class));
 * ```
 *
 * @template T as object
 * @implements ReturnTypeInterface<PaginatorInterface<T>>
 */
final class PaginatorType implements ReturnTypeInterface
{
    public function __construct(
        /**
         * The entity class name
         *
         * @var class-string<T>
         */
        private readonly string $entity,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function unwrappedType(): ?string
    {
        return $this->entity;
    }

    /**
     * {@inheritdoc}
     */
    public function cast(mixed $value): PaginatorInterface
    {
        if (!$value instanceof PaginatorInterface) {
            throw new TypeError('Invalid return type');
        }

        /** @var PaginatorInterface<T> */
        return $value;
    }

    /**
     * Create the type for the given entity
     *
     * @param class-string<R> $entity The entity class name
     * @return self<R>
     *
     * @template R as object
     */
    public static function of(string $entity): self
    {
        return new self($entity);
    }
}
