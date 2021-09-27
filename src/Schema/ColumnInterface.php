<?php

namespace Bdf\Prime\Schema;

use Bdf\Prime\Platform\PlatformTypeInterface;

/**
 * Interface for one table's column
 *
 * /!\ Implementation classes should be immutable
 */
interface ColumnInterface
{
    /**
     * Get the column name
     *
     * @return string
     */
    public function name();

    /**
     * Get the platform type for this column
     *
     * @return PlatformTypeInterface
     */
    public function type();

    /**
     * Get the default value
     * The value MUST be a DBAL value, and should be converted to platform value
     *
     * @return mixed
     */
    public function defaultValue();

    /**
     * Get the column max length
     *
     * @return int|null
     */
    public function length();

    /**
     * Does the column is on auto increment
     *
     * @return bool
     */
    public function autoIncrement();

    /**
     * Does the encoded value should be unsigned ?
     *
     * @return bool
     */
    public function unsigned();

    /**
     * Fixed column length (i.e. CHAR vs VARCHAR) ?
     *
     * @return bool
     */
    public function fixed();

    /**
     * Does the value can be null ?
     *
     * @return bool
     */
    public function nillable();

    /**
     * Get the column comment
     *
     * @return string|null
     */
    public function comment();

    /**
     * Get the decimal precision
     *
     * @return int|null
     */
    public function precision();

    /**
     * The number of digit after the decimal mark
     *
     * @return int|null
     */
    public function scale();

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
