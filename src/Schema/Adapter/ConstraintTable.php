<?php

namespace Bdf\Prime\Schema\Adapter;

use Bdf\Prime\Schema\ConstraintSetInterface;
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
    public function name()
    {
        return $this->table->name();
    }

    /**
     * {@inheritdoc}
     */
    public function column($name)
    {
        return $this->table->column($name);
    }

    /**
     * {@inheritdoc}
     */
    public function columns()
    {
        return $this->table->columns();
    }

    /**
     * {@inheritdoc}
     */
    public function indexes()
    {
        return $this->table->indexes();
    }

    /**
     * {@inheritdoc}
     */
    public function constraints()
    {
        return $this->constraints;
    }

    /**
     * {@inheritdoc}
     */
    public function options()
    {
        return $this->table->options();
    }

    /**
     * {@inheritdoc}
     */
    public function option($name)
    {
        return $this->table->option($name);
    }
}
