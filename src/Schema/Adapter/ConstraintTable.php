<?php

namespace Bdf\Prime\Schema\Adapter;

use Bdf\Prime\Schema\ColumnInterface;
use Bdf\Prime\Schema\ConstraintSetInterface;
use Bdf\Prime\Schema\IndexSetInterface;
use Bdf\Prime\Schema\TableInterface;

/**
 * Redefine the constraint set of a table
 */
final class ConstraintTable implements TableInterface
{
    /**
     * @var TableInterface
     */
    private $table;

    /**
     * @var ConstraintSetInterface
     */
    private $constraints;


    /**
     * ConstraintTable constructor.
     *
     * @param TableInterface $table
     * @param ConstraintSetInterface $constraints
     */
    public function __construct(TableInterface $table, ConstraintSetInterface $constraints)
    {
        $this->table       = $table;
        $this->constraints = $constraints;
    }

    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return $this->table->name();
    }

    /**
     * {@inheritdoc}
     */
    public function column(string $name): ColumnInterface
    {
        return $this->table->column($name);
    }

    /**
     * {@inheritdoc}
     */
    public function columns(): array
    {
        return $this->table->columns();
    }

    /**
     * {@inheritdoc}
     */
    public function indexes(): IndexSetInterface
    {
        return $this->table->indexes();
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
        return $this->table->options();
    }

    /**
     * {@inheritdoc}
     */
    public function option(string $name)
    {
        return $this->table->option($name);
    }
}
