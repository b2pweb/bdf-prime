<?php

namespace Bdf\Prime\Bus\Query\Execution;

use Bdf\Prime\Bus\Query\Configurator\Projection;
use Bdf\Prime\Query\QueryInterface;
use Bdf\Prime\Query\ReadCommandInterface;

/**
 * Execute the query and get a single column of the first row
 *
 * The result will be the content of the column, or null if the column is not found.
 * The value is not parsed by the column type defined on the mapper, so the DBAL raw value is returned.
 *
 * Usage:
 * ```php
 * #[PrimeQuery(MyEntity::class, method: new FirstRowColumn('name'))]
 * class GetNameQuery
 * {
 *     // ...
 * }
 * ```
 *
 * Note: {@see Projection} has no effect on this method
 *
 * @see QueryInterface::inRow() The actual method executed
 */
final class FirstRowColumn implements QueryExecutionMethodInterface
{
    public function __construct(
        /**
         * The column name to return
         */
        private readonly string $column,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function execute(ReadCommandInterface $query, array $options = []): mixed
    {
        return $query->inRow($this->column);
    }
}
