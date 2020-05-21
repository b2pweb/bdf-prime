<?php

namespace Bdf\Prime\Connection\Result;

/**
 * Result set for update operation
 * Will return only the modified rows count
 */
final class UpdateResultSet extends \EmptyIterator implements ResultSetInterface
{
    /**
     * @var integer
     */
    private $count;


    /**
     * UpdateResultSet constructor.
     *
     * @param int $count
     */
    public function __construct($count)
    {
        $this->count = $count;
    }

    /**
     * {@inheritdoc}
     */
    public function fetchMode($mode, $options = null)
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function all()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return $this->count;
    }
}