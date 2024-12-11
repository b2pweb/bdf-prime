<?php

namespace Bdf\Prime\Connection\Result;

use Bdf\Prime\Connection\Result\FetchStrategy\AssociativeDoctrineFetch;
use Bdf\Prime\Connection\Result\FetchStrategy\ClassDoctrineFetch;
use Bdf\Prime\Connection\Result\FetchStrategy\ColumnDoctrineFetch;
use Bdf\Prime\Connection\Result\FetchStrategy\DoctrineFetchStrategyInterface;
use Bdf\Prime\Connection\Result\FetchStrategy\ListDoctrineFetch;
use Bdf\Prime\Connection\Result\FetchStrategy\ObjectDoctrineFetch;
use Bdf\Prime\Exception\DBALException;
use Doctrine\DBAL\Result;

/**
 * Adapt Doctrine result to result set
 *
 * @template T
 * @implements ResultSetInterface<T>
 */
final class DoctrineResultSet implements ResultSetInterface
{
    /**
     * @var Result
     * @readonly
     */
    private Result $result;

    private int $key = 0;

    /**
     * @var T|false|null
     */
    private $current;

    /**
     * @var DoctrineFetchStrategyInterface<T>
     */
    private DoctrineFetchStrategyInterface $strategy;


    /**
     * PdoResultSet constructor.
     *
     * @param Result $result
     * @psalm-this-out DoctrineResultSet<array<string, mixed>>
     */
    public function __construct(Result $result)
    {
        $this->result = $result;
        /** @var DoctrineResultSet<array<string, mixed>> $this */
        $this->strategy = AssociativeDoctrineFetch::instance();
    }

    /**
     * {@inheritdoc}
     */
    #[\ReturnTypeWillChange]
    public function current()
    {
        if ($this->current === null) {
            $this->rewind();
        }

        /** @var T */
        return $this->current;
    }

    /**
     * {@inheritdoc}
     */
    public function next(): void
    {
        $this->current = $this->strategy->one($this->result);
        ++$this->key;
    }

    /**
     * {@inheritdoc}
     */
    #[\ReturnTypeWillChange]
    public function key()
    {
        return $this->key;
    }

    /**
     * {@inheritdoc}
     */
    public function valid(): bool
    {
        return $this->current !== false;
    }

    /**
     * {@inheritdoc}
     *
     * @psalm-suppress DeprecatedConstant
     */
    public function fetchMode($mode, $options = null)
    {
        switch ($mode) {
            case self::FETCH_ASSOC:
                return $this->asAssociative();

            case self::FETCH_NUM:
                return $this->asList();

            case self::FETCH_OBJECT:
                return $this->asObject();

            case self::FETCH_COLUMN:
                return $this->asColumn($options ?? 0);

            case self::FETCH_CLASS:
                return $this->asClass($options);

            default:
                throw new DBALException('Unsupported fetch mode '.$mode);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function asAssociative(): ResultSetInterface
    {
        /** @var DoctrineResultSet<array<string, mixed>> $this */
        $this->strategy = AssociativeDoctrineFetch::instance();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function asList(): ResultSetInterface
    {
        /** @var DoctrineResultSet<list<mixed>> $this */
        $this->strategy = ListDoctrineFetch::instance();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function asObject(): ResultSetInterface
    {
        /** @var DoctrineResultSet<\stdClass> $this */
        $this->strategy = ObjectDoctrineFetch::instance();

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
        /** @var DoctrineResultSet<E> $this */
        $this->strategy = new ClassDoctrineFetch($className, $constructorArguments);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function asColumn(int $column = 0): ResultSetInterface
    {
        /** @var DoctrineResultSet<mixed> $this */
        $this->strategy = new ColumnDoctrineFetch($column);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function all(): array
    {
        return $this->strategy->all($this->result);
    }

    /**
     * {@inheritdoc}
     */
    public function rewind(): void
    {
        $this->current = $this->strategy->one($this->result);
        $this->key = 0;
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return $this->result->rowCount();
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

    /**
     * Close the cursor on result set destruction
     */
    public function __destruct()
    {
        $this->result->free();
    }
}
