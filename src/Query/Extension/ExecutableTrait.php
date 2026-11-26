<?php

namespace Bdf\Prime\Query\Extension;

use Bdf\Prime\Collection\CollectionInterface;
use Bdf\Prime\Connection\Result\ResultSetInterface;
use Bdf\Prime\Exception\PrimeException;
use Bdf\Prime\Query\Contract\ReadOperation;
use Bdf\Prime\Query\Expression\ExpressionInterface;
use Bdf\Prime\Query\QueryInterface;

/**
 * Trait for provide execute() wrapper methods
 *
 * @psalm-require-implements \Bdf\Prime\Query\ReadCommandInterface
 * @template R as object|array
 */
trait ExecutableTrait
{
    /**
     * {@inheritdoc}
     * @see QueryInterface::all()
     *
     * @return R[]|CollectionInterface<R>
     */
    #[ReadOperation]
    public function all(string|array|null $columns = null): array|CollectionInterface
    {
        return $this->postProcessResult($this->execute($columns));
    }

    /**
     * {@inheritdoc}
     * @see QueryInterface::first()
     *
     * @return R|null
     */
    #[ReadOperation]
    public function first(string|array|null $columns = null): array|object|null
    {
        foreach ($this->limit(1)->all($columns) as $entity) {
            return $entity;
        }

        return null;
    }

    /**
     * {@inheritdoc}
     * @see QueryInterface::inRows()
     *
     * @return list<mixed>
     */
    #[ReadOperation]
    public function inRows(string|ExpressionInterface $column): array
    {
        return $this->execute($column)->asColumn()->all();
    }

    /**
     * {@inheritdoc}
     * @see QueryInterface::inRow()
     */
    #[ReadOperation]
    public function inRow(string|ExpressionInterface $column): mixed
    {
        foreach ($this->limit(1)->execute($column)->asColumn() as $value) {
            return $value;
        }

        return null;
    }

    /**
     * Post processors.
     * Wrap data with defined wrapper. Run the post processors on rows
     *
     * @param ResultSetInterface<array<string, mixed>> $data
     *
     * @return array|CollectionInterface
     */
    abstract public function postProcessResult(ResultSetInterface $data): iterable;

    /**
     * {@inheritdoc}
     *
     * @return ResultSetInterface<array<string, mixed>>
     *
     * @see QueryInterface::execute()
     * @throws PrimeException
     */
    #[ReadOperation]
    abstract public function execute(string|ExpressionInterface|array|null $columns = null): ResultSetInterface;

    /**
     * {@inheritdoc}
     *
     * @see QueryInterface::limit()
     */
    abstract public function limit(?int $limit, ?int $offset = null);
}
