<?php

namespace Bdf\Prime\Schema\Adapter\Doctrine;

use Bdf\Prime\Schema\Bag\IndexSet;
use Bdf\Prime\Schema\Constraint\ConstraintSet;
use Bdf\Prime\Schema\TableInterface;
use Bdf\Prime\Types\TypesRegistryInterface;
use Doctrine\DBAL\Schema\Table;

/**
 * Adapt doctrine table to prime table
 */
final class DoctrineTable implements TableInterface
{
    /**
     * @var Table
     */
    private $table;

    /**
     * @var TypesRegistryInterface
     */
    private $types;


    /**
     * DoctrineTable constructor.
     *
     * @param Table $table
     * @param TypesRegistryInterface $types
     */
    public function __construct(Table $table, TypesRegistryInterface $types)
    {
        $this->table = $table;
        $this->types = $types;
    }

    /**
     * {@inheritdoc}
     */
    public function name()
    {
        return $this->table->getName();
    }

    /**
     * {@inheritdoc}
     */
    public function column($name)
    {
        return new DoctrineColumn($this->table->getColumn($name), $this->types);
    }

    /**
     * {@inheritdoc}
     */
    public function columns()
    {
        $columns = [];

        foreach ($this->table->getColumns() as $column) {
            $col = new DoctrineColumn($column, $this->types);
            $columns[$col->name()] = $col;
        }

        return $columns;
    }

    /**
     * {@inheritdoc}
     */
    public function primary()
    {
        $primary = $this->table->getPrimaryKey();

        if ($primary === null) {
            return null;
        }

        return new DoctrineIndex($primary);
    }

    /**
     * {@inheritdoc}
     */
    public function indexes()
    {
        return new IndexSet(
            array_map(
                function ($index) {
                    return new DoctrineIndex($index);
                },
                $this->table->getIndexes()
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function constraints()
    {
        return new ConstraintSet(
            array_map(
                function ($fk) {
                    return new DoctrineForeignKey($fk);
                },
                $this->table->getForeignKeys()
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function options()
    {
        return $this->table->getOptions();
    }

    /**
     * {@inheritdoc}
     */
    public function option($name)
    {
        return $this->table->getOption($name);
    }

    /**
     * Get the doctrine table
     *
     * @return Table
     */
    public function toDoctrine()
    {
        return $this->table;
    }
}
