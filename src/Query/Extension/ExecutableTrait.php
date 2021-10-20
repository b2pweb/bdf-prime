<?php

namespace Bdf\Prime\Query\Extension;

use Bdf\Prime\Collection\CollectionInterface;
use Bdf\Prime\Connection\Result\ResultSetInterface;
use Bdf\Prime\Exception\PrimeException;
use Bdf\Prime\Query\Contract\ReadOperation;
use Bdf\Prime\Query\QueryInterface;

/**
 * Trait for provide execute() wrapper methods
 *
 * @psalm-require-implements \Bdf\Prime\Query\ReadCommandInterface
 */
trait ExecutableTrait
{
    /**
     * {@inheritdoc}
     * @see QueryInterface::all()
     */
    #[ReadOperation]
    public function all($columns = null)
    {
        return $this->postProcessResult($this->execute($columns));
    }

    /**
     * {@inheritdoc}
     * @see QueryInterface::first()
     */
    #[ReadOperation]
    public function first($columns = null)
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
    public function inRows(string $column): array
    {
        return $this->execute($column)->asColumn()->all();
    }

    /**
     * {@inheritdoc}
     * @see QueryInterface::inRow()
     */
    #[ReadOperation]
    public function inRow(string $column)
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
    abstract public function execute($columns = null): ResultSetInterface;

    /**
     * {@inheritdoc}
     *
     * @see QueryInterface::limit()
     */
    abstract public function limit(?int $limit, ?int $offset = null);
}
