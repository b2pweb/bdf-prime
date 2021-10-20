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
    public function name(): string;

    /**
     * Get the platform type for this column
     *
     * @return PlatformTypeInterface
     */
    public function type(): PlatformTypeInterface;

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
    public function length(): ?int;

    /**
     * Does the column is on auto increment
     *
     * @return bool
     */
    public function autoIncrement(): bool;

    /**
     * Does the encoded value should be unsigned ?
     *
     * @return bool
     */
    public function unsigned(): bool;

    /**
     * Fixed column length (i.e. CHAR vs VARCHAR) ?
     *
     * @return bool
     */
    public function fixed(): bool;

    /**
     * Does the value can be null ?
     *
     * @return bool
     */
    public function nillable(): bool;

    /**
     * Get the column comment
     *
     * @return string|null
     */
    public function comment(): ?string;

    /**
     * Get the decimal precision
     *
     * @return int|null
     */
    public function precision(): ?int;

    /**
     * The number of digit after the decimal mark
     *
     * @return int|null
     */
    public function scale(): ?int;

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
