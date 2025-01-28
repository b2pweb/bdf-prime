<?php

namespace Bdf\Prime\Bus\Query\Configurator;

use Attribute;
use Bdf\Prime\Bus\Query\Execution\QueryExecutionMethod;

/**
 * Define the annotated property as a page number for the query, when used in context of pagination
 *
 * Usage:
 * ```php
 * #[PrimeQuery(MyEntity::class, method: QueryExecutionMethod::Paginate)]
 * class MyQuery
 * {
 *     public function __construct(
 *         // ...
 *         #[Page]
 *         public readonly int $page = 1,
 *     ) {}
 * }
 * ```
 *
 * @see QueryExecutionMethod::Paginate Should be used to take the page into account
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Page implements ExecutionOptionInterface
{
    public function __construct(
        /**
         * Define the default page number, when the property is not set
         *
         * @var positive-int
         */
        private readonly int $page = 1,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function executionOptions(mixed $propertyValue = null): array
    {
        return ['page' => $propertyValue ?? $this->page];
    }
}
