<?php

namespace Bdf\Prime\Schema\Inflector;

/**
 * InflectorInterface
 */
interface InflectorInterface
{
    /**
     * Get the class name from table name
     *
     * @param string $table
     *
     * @return string
     */
    public function getClassName($table);

    /**
     * Get the property name from a field name
     *
     * @param string $table
     * @param string $field
     *
     * @return string
     */
    public function getPropertyName($table, $field);

    /**
     * Get the sequence table name from table name
     *
     * @param string $table
     *
     * @return string
     */
    public function getSequenceName($table);
}