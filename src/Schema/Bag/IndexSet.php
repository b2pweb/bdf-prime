<?php

namespace Bdf\Prime\Schema\Bag;

use Bdf\Prime\Schema\IndexInterface;
use Bdf\Prime\Schema\IndexSetInterface;

use function array_filter;

/**
 * Index set class
 * An index set represents a set of index, with a primary key
 * The indexes names are case insensitive
 */
final class IndexSet implements IndexSetInterface
{
    /**
     * @var IndexInterface[]
     */
    private array $indexes;


    /**
     * IndexSet constructor.
     *
     * @param IndexInterface[] $indexes
     */
    public function __construct(array $indexes)
    {
        $this->indexes = [];

        foreach ($indexes as $index) {
            $this->indexes[strtolower($index->name())] = $index;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function primary(): ?IndexInterface
    {
        foreach ($this->indexes as $index) {
            if ($index->primary()) {
                return $index;
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function all(): array
    {
        return $this->indexes;
    }

    /**
     * {@inheritdoc}
     */
    public function secondaries(): array
    {
        return array_filter($this->indexes, static fn (IndexInterface $index) => !$index->primary());
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $name): IndexInterface
    {
        return $this->indexes[strtolower($name)];
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $name): bool
    {
        return isset($this->indexes[strtolower($name)]);
    }
}
