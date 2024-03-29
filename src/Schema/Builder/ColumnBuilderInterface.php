<?php

namespace Bdf\Prime\Schema\Builder;

use Bdf\Prime\Platform\PlatformTypeInterface;
use Bdf\Prime\Schema\ColumnInterface;

/**
 * Interface for build table's columns
 *
 * The name and type should be set at constructor
 * The column builder generate a column object AND indexes (unique())
 *
 * <code>
 * $builder
 *     ->autoincrement()
 *     ->unsigned()
 *     ->unique()
 * ;
 * </code>
 */
interface ColumnBuilderInterface
{
    /**
     * Set the column name
     *
     * @param string $name
     *
     * @return $this
     */
    public function name(string $name);

    /**
     * Set the column type
     *
     * @param PlatformTypeInterface $type
     *
     * @return $this
     */
    public function type(PlatformTypeInterface $type);

    /**
     * Specify the autoincrement key.
     * /!\ Unlike FieldBuilder, this method will not set the column as primary key
     *
     * @param bool $flag         Activate/Deactivate autoincrement
     *
     * @return $this
     */
    public function autoincrement(bool $flag = true);

    /**
     * Set length of current string field
     *
     * @param int|null $length        The length of the value
     *
     * @return $this             This builder instance
     */
    public function length(?int $length);

    /**
     * Set comment on current field
     *
     * @param string|null $comment     The comment
     *
     * @return $this             This builder instance
     */
    public function comment(?string $comment);

    /**
     * Set the default value of current field
     * The value will be converted to the DB representation
     *
     * @param mixed $value       The repository name
     *
     * @return $this             This builder instance
     */
    public function setDefault($value);

    /**
     * Set the precision and scale of a digit
     *
     * @param int|null $precision     The number of significant digits that are stored for values
     * @param int|null $scale         The number of digits that can be stored following the decimal point
     *
     * @return $this             This builder instance
     */
    public function precision(?int $precision, ?int $scale = 0);

    /**
     * Set nillable flag of current field
     *
     * @param bool $flag         Activate/Deactivate nillable
     *
     * @return $this             This builder instance
     */
    public function nillable(bool $flag = true);

    /**
     * Set unsigned flag of current field
     *
     * @param bool $flag         Activate/Deactivate unsigned
     *
     * @return $this             This builder instance
     */
    public function unsigned(bool $flag = true);

    /**
     * Set unique flag of current field
     *
     * @param bool|string $index The index name. True to generate one
     *
     * @return $this             This builder instance
     */
    public function unique($index = true);

    /**
     * Set fixed flag of current field.
     *
     * Fix length of a string
     *
     * @param bool $flag         Activate/Deactivate unique
     *
     * @return $this             This builder instance
     */
    public function fixed(bool $flag = true);

    /**
     * Set column options
     *
     * @param array $options array of options
     *
     * @return $this
     */
    public function options(array $options);

    /**
     * Build the column object
     *
     * @return ColumnInterface
     */
    public function build(): ColumnInterface;

    /**
     * Get related indexes
     *
     * @return array Array of indexes, in form : [ [name] => [type] ]
     */
    public function indexes(): array;

    /**
     * Get the column name
     *
     * @return string
     */
    public function getName(): string;
}
