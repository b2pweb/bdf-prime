<?php

namespace Bdf\Prime\Schema\Bag;

use Bdf\Prime\Schema\ColumnInterface;
use Bdf\Prime\Schema\Constraint\ConstraintSet;
use Bdf\Prime\Schema\ConstraintSetInterface;
use Bdf\Prime\Schema\IndexSetInterface;
use Bdf\Prime\Schema\TableInterface;

/**
 * Simple table object
 */
final class Table implements TableInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var ColumnInterface[]
     */
    private $columns;

    /**
     * @var IndexSetInterface
     */
    private $indexes;

    /**
     * @var array
     */
    private $options;

    /**
     * @var ConstraintSetInterface
     */
    private $constraints;


    /**
     * Table constructor.
     *
     * @param string $name
     * @param ColumnInterface[] $columns
     * @param IndexSetInterface $indexes
     * @param ConstraintSetInterface|null $constraints
     * @param array $options
     */
    public function __construct(string $name, array $columns, IndexSetInterface $indexes, ?ConstraintSetInterface $constraints = null, array $options = [])
    {
        $this->name = $name;
        $this->indexes = $indexes;
        $this->constraints = $constraints ?? new ConstraintSet([]);
        $this->options = $options;

        $this->columns = [];

        foreach ($columns as $column) {
            $this->columns[$column->name()] = $column;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function column(string $name): ColumnInterface
    {
        return $this->columns[$name];
    }

    /**
     * {@inheritdoc}
     */
    public function columns(): array
    {
        return $this->columns;
    }

    /**
     * {@inheritdoc}
     */
    public function indexes(): IndexSetInterface
    {
        return $this->indexes;
    }

    /**
     * {@inheritdoc}
     */
    public function constraints(): ConstraintSetInterface
    {
        return $this->constraints;
    }

    /**
     * {@inheritdoc}
     */
    public function options(): array
    {
        return $this->options;
    }

    /**
     * {@inheritdoc}
     */
    public function option(string $name)
    {
        return $this->options[$name];
    }
}
