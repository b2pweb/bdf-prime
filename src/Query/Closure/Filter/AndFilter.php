<?php

namespace Bdf\Prime\Query\Closure\Filter;

use ArrayAccess;
use BadMethodCallException;
use Countable;
use Generator;
use IteratorAggregate;

use function array_push;

/**
 * @implements IteratorAggregate<int, AtomicFilter|OrFilter>
 * @implements ArrayAccess<int, AtomicFilter|OrFilter>
 */
final class AndFilter implements IteratorAggregate, Countable, ArrayAccess
{
    /**
     * @var list<AtomicFilter|OrFilter>
     */
    public array $filters = [];

    /**
     * @param AtomicFilter|OrFilter|AndFilter ...$filters
     */
    public function __construct(...$filters)
    {
        foreach ($filters as $filter) {
            if ($filter instanceof AndFilter) {
                array_push($this->filters, ...$filter->filters);
            } else {
                $this->filters[] = $filter;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator(): Generator
    {
        yield from $this->filters;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset): bool
    {
        return isset($this->filters[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->filters[$offset];
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value): void
    {
        throw new BadMethodCallException('Filters are immutable');
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset): void
    {
        throw new BadMethodCallException('Filters are immutable');
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return count($this->filters);
    }
}
