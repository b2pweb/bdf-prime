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
    public function asAssociative(): ResultSetInterface
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function asList(): ResultSetInterface
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function asObject(): ResultSetInterface
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function asClass(string $className, array $constructorArguments = []): ResultSetInterface
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function asColumn(int $column = 0): ResultSetInterface
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

    /**
     * {@inheritdoc}
     */
    public function isRead(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isWrite(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function hasWrite(): bool
    {
        return $this->count > 0;
    }
}