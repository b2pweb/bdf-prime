<?php

namespace Bdf\Prime\Schema;

/**
 * Interface for represents index
 */
interface IndexInterface
{
    const TYPE_SIMPLE  = 0;
    const TYPE_UNIQUE  = 1;
    const TYPE_PRIMARY = 3; // 3 = 2|1

    /**
     * Get the index name
     *
     * @return string
     */
    public function name();

    /**
     * Check if the index is unique
     *
     * @return bool
     */
    public function unique();

    /**
     * Check if the index is primary
     *
     * @return bool
     */
    public function primary();

    /**
     * Get the index type
     *
     * @return int
     */
    public function type();

    /**
     * Get list of composed fields
     *
     * @return string[]
     */
    public function fields();

    /**
     * Check if the index is composite (i.e. have multiple fields)
     *
     * @return bool
     */
    public function isComposite();

    /**
     * Gets the index options
     *
     * @return array
     */
    public function options();

    /**
     * Get options for one field
     * If the field has no options, an empty array is returned
     *
     * @param string $field The field name
     *
     * @return array
     */
    public function fieldOptions($field);
}
