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
    public function name();

    /**
     * Get the column object
     *
     * @param string $name
     *
     * @return ColumnInterface
     */
    public function column($name);

    /**
     * Get all columns, index by there names
     *
     * @return ColumnInterface[]
     */
    public function columns();

    /**
     * Get set of indexes
     *
     * @return IndexSetInterface
     */
    public function indexes();

    /**
     * Get set of constraints
     *
     * @return ConstraintSetInterface
     */
    public function constraints();

    /**
     * Get the array of options
     *
     * @return array
     */
    public function options();

    /**
     * Get one option
     *
     * @param string $name The option name
     *
     * @return mixed
     */
    public function option($name);
}
