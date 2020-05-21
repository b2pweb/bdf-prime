<?php

namespace Bdf\Prime\Schema\Builder;

use Bdf\Prime\Platform\PlatformTypeInterface;
use Bdf\Prime\Schema\IndexInterface;
use Bdf\Prime\Schema\TableInterface;

/**
 * Interface for building tables
 *
 * This builder is low level.
 * To build table with high level, use @see TypesHelperTableBuilder
 *
 * /!\ The columns types should be @see PlatformTypeInterface
 *     Call of add() will returns @see ColumnBuilderInterface
 *
 * <code>
 * $builder->add('id', new SqlStringType(TypeInterface::BIGINT));
 * $builder->primary();
 * $builder->add('name', new SqlStringType(TypeInterface::STRING))->unique();
 * </code>
 */
interface TableBuilderInterface
{
    /**
     * Set the table name
     *
     * @param string $name
     *
     * @return $this
     */
    public function name($name);

    /**
     * Set table options
     *
     * @param  array  $options
     *
     * @return $this
     */
    public function options(array $options);

    /**
     * Set the indexes of the table
     *
     * <code>
     * $table->indexes([
     *     'index_name' => 'field',
     *     'index_name' => ['field1', 'field2'],
     *     'field2',
     *     'with_options' => [
     *         'fields' => ['first_name', 'address' => ['length' => 32]],
     *         'type' => IndexInterface::TYPE_UNIQUE,
     *         'options' => ['opt' => 'val'],
     *     ]
     * ]);
     * </code>
     *
     * @param  array  $indexes
     *
     * @return $this
     */
    public function indexes(array $indexes);

    /**
     * Add a new index on the table
     *
     * <code>
     * $builder
     *     ->index('name') // Add simple index of field "name"
     *     ->index('email', IndexInterface::TYPE_UNIQUE) // Add unique index on "email"
     *     ->index('description', IndexInterface::TYPE_SIMPLE, 'descr_search', ['fulltext' => true]) // Add fulltext index on field "description"
     *     ->index(['type', 'date']) // Add index on fields "type" and "date"
     *     ->index(['reference' => ['length' => 24]], IndexInterface::TYPE_UNIQUE) // Add unique index on first 12 chars of "reference"
     * ;
     * </code>
     *
     * @param string|string[] $columns The columns composed the index. If a string is passed, it will be transformed to an array with single column
     * @param int $type The index type (one of the IndexInterface::TYPE_* constant)
     * @param string $name The index name. If not specified, it will be generated
     * @param array $options Options of the index
     *
     * @return $this
     */
    public function index($columns, $type = IndexInterface::TYPE_SIMPLE, $name = null, array $options = []);

    /**
     * Specify the primary key(s) for the table.
     *
     * @param  string|array  $columns   The name of columns. Null to select the current one
     * @param  string        $name      The name of the index. Null to generate one
     *
     * @return $this
     */
    public function primary($columns = null, $name = null);

    /**
     * Add a new column to the table
     *
     * @param string $column The column name
     * @param PlatformTypeInterface $type The column type
     * @param array $options Array fof column options
     *
     * Allowed options:
     *   * autoincrement        :
     *   * columnDefinition     :
     *   * comment              :
     *   * customSchemaOptions  :
     *   * default              :
     *   * fixed                :
     *   * length               :
     *   * nillable             :
     *   * notnull              : Negation of nillable
     *   * platformOptions      :
     *   * precision            :
     *   * primary              :
     *   * scale                :
     *   * unique               :
     *   * unsigned             :
     *
     * @return ColumnBuilderInterface The new created column
     */
    public function add($column, PlatformTypeInterface $type, array $options = []);

    /**
     * Get a column by its name
     *
     * @param  string|null $name The column name, or null to get the last added column
     *
     * @return ColumnBuilderInterface
     */
    public function column($name = null);

    /**
     * Adds a foreign key constraint.
     *
     * Name is inferred from the local columns.
     *
     * @param TableInterface|string $foreignTable Table schema instance or table name
     * @param array        $localColumnNames
     * @param array        $foreignColumnNames
     * @param array        $options
     * @param string|null  $constraintName
     *
     * @return $this
     */
    public function foreignKey($foreignTable, array $localColumnNames, array $foreignColumnNames, array $options = [], $constraintName = null);

    /**
     * Build the table object
     *
     * @return TableInterface
     */
    public function build();
}
