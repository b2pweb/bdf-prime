<?php

namespace Bdf\Prime\Query\Extension;

use Bdf\Prime\Query\QueryInterface;

/**
 * Trait for provide execute() wrapper methods
 */
trait ExecutableTrait
{
    /**
     * @see QueryInterface::all()
     */
    public function all($columns = null)
    {
        return $this->postProcessResult($this->execute($columns));
    }

    /**
     * @see QueryInterface::first()
     */
    public function first($columns = null)
    {
        $result = $this->limit(1)->all($columns);

        return isset($result[0]) ? $result[0] : null;
    }

    /**
     * @see QueryInterface::inRows()
     */
    public function inRows($column)
    {
        $result = [];

        foreach ($this->execute($column) as $data) {
            $result[] = reset($data);
        }

        return $result;
    }

    /**
     * @see QueryInterface::inRow()
     */
    public function inRow($column)
    {
        $result = $this->limit(1)->execute($column);

        return isset($result[0]) ? reset($result[0]) : null;
    }

    /**
     * Post processors.
     * Wrap data with defined wrapper. Run the post processors on rows
     *
     * @param array  $data
     *
     * @return array
     */
    abstract public function postProcessResult($data);

    /**
     * @see QueryInterface::execute()
     */
    abstract public function execute($columns = null);

    /**
     * @see QueryInterface::limit()
     * @return $this
     */
    abstract public function limit($limit, $offset = null);
}
