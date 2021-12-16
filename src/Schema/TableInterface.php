<?php

namespace Bdf\Prime\Schema;

/**
 * Interface for represents schema's tables
 *
 * /!\ Implementation classes should be immutable
 */
interface TableInterface
{
    /**
     * Get the table name
     *
     * @return string
     */
    public function name(): string;

    /**
     * Get the column object
     *
     * @param string $name
     *
     * @return ColumnInterface
     */
    public function column(string $name): ColumnInterface;

    /**
     * Get all columns, index by there names
     *
     * @return array<string, ColumnInterface>
     */
    public function columns(): array;

    /**
     * Get set of indexes
     *
     * @return IndexSetInterface
     */
    public function indexes(): IndexSetInterface;

    /**
     * Get set of constraints
     *
     * @return ConstraintSetInterface
     */
    public function constraints(): ConstraintSetInterface;

    /**
     * Get the array of options
     *
     * @return array<string, mixed>
     */
    public function options(): array;

    /**
     * Get one option
     *
     * @param string $name The option name
     *
     * @return mixed
     */
    public function option(string $name);
}
