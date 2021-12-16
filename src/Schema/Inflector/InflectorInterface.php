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
     * @return class-string
     */
    public function getClassName(string $table): string;

    /**
     * Get the property name from a field name
     *
     * @param string $table
     * @param string $field
     *
     * @return string
     */
    public function getPropertyName(string $table, string $field): string;

    /**
     * Get the sequence table name from table name
     *
     * @param string $table
     *
     * @return string
     */
    public function getSequenceName(string $table): string;
}