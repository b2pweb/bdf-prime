<?php

namespace Bdf\Prime\Connection\Result;

use ArrayIterator;
use Bdf\Prime\Connection\Result\FetchStrategy\ArrayFetchStrategyInterface;
use Bdf\Prime\Connection\Result\FetchStrategy\AssociativeArrayFetch;
use Bdf\Prime\Connection\Result\FetchStrategy\ClassArrayFetch;
use Bdf\Prime\Connection\Result\FetchStrategy\ColumnArrayFetch;
use Bdf\Prime\Connection\Result\FetchStrategy\ListArrayFetch;
use Bdf\Prime\Connection\Result\FetchStrategy\ObjectArrayFetch;
use Bdf\Prime\Exception\DBALException;

/**
 * Wrap simple associative array to ResultSet
 * This result is useful for caches
 *
 * @template T
 * @implements ResultSetInterface<T>
 */
final class ArrayResultSet extends ArrayIterator implements ResultSetInterface
{
    /**
     * @var ArrayFetchStrategyInterface<T>
     */
    private ArrayFetchStrategyInterface $strategy;

    /**
     * @param list<array<string, mixed>> $array
     * @param int $flags
     * @psalm-this-out ArrayResultSet<array<string, mixed>>
     */
    public function __construct($array = [], $flags = 0)
    {
        parent::__construct($array, $flags);

        /** @var ArrayResultSet<array<string, mixed>> $this */
        $this->strategy = AssociativeArrayFetch::instance();
    }

    /**
     * {@inheritdoc}
     */
    public function fetchMode($mode, $options = null)
    {
        switch ($mode) {
            case self::FETCH_ASSOC:
                return $this->asAssociative();

            case self::FETCH_NUM:
                return $this->asList();

            case self::FETCH_COLUMN:
                return $this->asColumn($options ?? 0);

            case self::FETCH_OBJECT:
                return $this->asObject();

            case self::FETCH_CLASS:
                return $this->asClass($options);

            default:
                throw new DBALException('Unsupported fetch mode ' . $mode);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function asAssociative(): ResultSetInterface
    {
        /** @var ArrayResultSet<array<string, mixed>> $this */
        $this->strategy = AssociativeArrayFetch::instance();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function asList(): ResultSetInterface
    {
        /** @var ArrayResultSet<list<mixed>> $this */
        $this->strategy = ListArrayFetch::instance();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function asObject(): ResultSetInterface
    {
        /** @var ArrayResultSet<\stdClass> $this */
        $this->strategy = ObjectArrayFetch::instance();

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param class-string<E> $className
     * @param list<mixed> $constructorArguments
     *
     * @return static<E>
     *
     * @template E
     */
    public function asClass(string $className, array $constructorArguments = []): ResultSetInterface
    {
        /** @var ArrayResultSet<E> $this */
        $this->strategy = new ClassArrayFetch($className, $constructorArguments);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function asColumn(int $column = 0): ResultSetInterface
    {
        /** @var ArrayResultSet<mixed> $this */
        $this->strategy = new ColumnArrayFetch($column);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function all(): array
    {
        return $this->strategy->all($this->getArrayCopy());
    }

    /**
     * {@inheritdoc}
     */
    #[\ReturnTypeWillChange]
    public function current()
    {
        $value = parent::current();

        if ($value === null) {
            return false;
        }

        return $this->strategy->one($value);
    }

    /**
     * {@inheritdoc}
     */
    public function rewind(): void
    {
        parent::rewind();
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return parent::count();
    }

    /**
     * {@inheritdoc}
     */
    public function isRead(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isWrite(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function hasWrite(): bool
    {
        return false;
    }
}
