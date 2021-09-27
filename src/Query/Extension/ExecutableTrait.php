<?php

namespace Bdf\Prime\Query\Extension;

use Bdf\Prime\Collection\CollectionInterface;
use Bdf\Prime\Exception\PrimeException;
use Bdf\Prime\Query\QueryInterface;
use Bdf\Prime\Query\Contract\ReadOperation;

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
        $result = $this->limit(1)->all($columns);

        return isset($result[0]) ? $result[0] : null;
    }

    /**
     * {@inheritdoc}
     * @see QueryInterface::inRows()
     */
    #[ReadOperation]
    public function inRows($column)
    {
        $result = [];

        foreach ($this->execute($column) as $data) {
            $result[] = reset($data);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     * @see QueryInterface::inRow()
     */
    #[ReadOperation]
    public function inRow($column)
    {
        $result = $this->limit(1)->execute($column);

        return isset($result[0]) ? reset($result[0]) : null;
    }

    /**
     * {@inheritdoc}
     *
     * Post processors.
     * Wrap data with defined wrapper. Run the post processors on rows
     *
     * @param array  $data
     *
     * @return array|CollectionInterface
     */
    abstract public function postProcessResult($data);

    /**
     * {@inheritdoc}
     * @see QueryInterface::execute()
     * @throws PrimeException
     */
    #[ReadOperation]
    abstract public function execute($columns = null);

    /**
     * {@inheritdoc}
     *
     * @see QueryInterface::limit()
     */
    abstract public function limit(?int $limit, ?int $offset = null);
}
