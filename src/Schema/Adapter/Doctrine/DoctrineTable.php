<?php

namespace Bdf\Prime\Schema\Adapter\Doctrine;

use Bdf\Prime\Schema\Bag\IndexSet;
use Bdf\Prime\Schema\ColumnInterface;
use Bdf\Prime\Schema\Constraint\ConstraintSet;
use Bdf\Prime\Schema\ConstraintSetInterface;
use Bdf\Prime\Schema\IndexSetInterface;
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
    public function name(): string
    {
        return $this->table->getName();
    }

    /**
     * {@inheritdoc}
     */
    public function column(string $name): ColumnInterface
    {
        return new DoctrineColumn($this->table->getColumn($name), $this->types);
    }

    /**
     * {@inheritdoc}
     */
    public function columns(): array
    {
        $columns = [];

        foreach ($this->table->getColumns() as $column) {
            $col = new DoctrineColumn($column, $this->types);
            $columns[$col->name()] = $col;
        }

        return $columns;
    }

    /**
     * Get the primary key index of the table
     *
     * @return DoctrineIndex|null The index, or null if the table has no primary key
     */
    public function primary(): ?DoctrineIndex
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
    public function indexes(): IndexSetInterface
    {
        return new IndexSet(
            array_map(
                static fn($index) => new DoctrineIndex($index),
                $this->table->getIndexes()
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function constraints(): ConstraintSetInterface
    {
        return new ConstraintSet(
            array_map(
                static fn($fk) => new DoctrineForeignKey($fk),
                $this->table->getForeignKeys()
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function options(): array
    {
        return $this->table->getOptions();
    }

    /**
     * {@inheritdoc}
     */
    public function option(string $name)
    {
        return $this->table->getOption($name);
    }

    /**
     * Get the doctrine table
     *
     * @return Table
     */
    public function toDoctrine(): Table
    {
        return $this->table;
    }
}
