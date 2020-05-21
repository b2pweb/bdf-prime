<?php

namespace Bdf\Prime\Query\Contract\Query;

use Bdf\Prime\Query\Custom\BulkInsert\BulkInsertQuery;

/**
 * Base type for insert queries
 *
 * <code>
 * // Simple insert
 * $insert
 *     ->into('person')
 *     ->values([
 *         'first_name' => 'John',
 *         'last_name'  => 'Doe'
 *     ])
 *     ->execute()
 * ;
 *
 * // Bulk insert
 * $insert
 *     ->bulk()
 *     ->values([
 *         'first_name' => 'Alan',
 *         'last_name'  => 'Smith'
 *     ])
 *     ->values([
 *         'first_name' => 'Mickey',
 *         'last_name'  => 'Mouse'
 *     ])
 *     ->execute()
 * ;
 * </code>
 */
interface InsertQueryInterface
{
    const MODE_INSERT = 'insert';
    const MODE_REPLACE = 'replace';
    const MODE_IGNORE = 'ignore';

    /**
     * Set the table table name
     *
     * @param string $table The table name
     *
     * @return $this
     */
    public function into($table);

    /**
     * Set the insert columns and types
     *
     * <code>
     * // Let Prime to resolve types
     * $insert->columns(['first_name', 'last_name', 'age']);
     *
     * // Explicitly define types
     * $insert->columns([
     *     'first_name' => 'string',
     *     'last_name'  => 'string',
     *     'age'        => 'integer'
     * ]);
     *
     * // Two syntax can be mixed
     * $insert->columns([
     *     'first_name', 'last_name',
     *     'age' => 'integer'
     * ]);
     * </code>
     *
     * @param array $columns The columns. For typed columns, set the column name as key and type as value
     *
     * @return $this
     */
    public function columns(array $columns);

    /**
     * Set values to insert
     *
     * <code>
     * // Simple insert
     * $insert
     *     ->into('perforn')
     *     ->values([
     *         'first_name' => 'John',
     *         'last_name'  => 'Doe'
     *     ])
     * ;
     *
     * // Append Alan Smith to bulk insert
     * $insert->bulk()->values([
     *     'first_name' => 'Alan',
     *     'last_name'  => 'Smith'
     * ]);
     *
     * // Overrides values (now contains only Mick Mouse)
     * $insert->values([
     *     'first_name' => 'Mickey',
     *     'last_name'  => 'Mouse'
     * ], true);
     * </code>
     *
     * @param array $data Data to insert, with key as column and value as insert value
     * @param bool $replace Overrides old values, or append value on the bulk insert. This parameter has effect only on bulk insert
     *
     * @return $this
     */
    public function values(array $data, $replace = false);

    /**
     * Change the insert mode
     * Prefer use ignore() or replace() methods
     *
     * @param string $mode One of the BulkInsertQuery::MODE_* constants
     *
     * @return $this
     *
     * @see BulkInsertQuery::ignore()
     * @see BulkInsertQuery::replace()
     */
    public function mode($mode);

    /**
     * Ignore the insert if the primary constrait failed
     *
     * @param bool $flag true to enable, or false to use normal insert
     *
     * @return $this
     */
    public function ignore($flag = true);

    /**
     * Force replace data :
     * - If the row does not exists, it'll be inserted
     * - If the row exists, it'll be replaced
     *
     * @param bool $flag true to enable, or false to use normal insert
     *
     * @return $this
     */
    public function replace($flag = true);

    /**
     * Enable bulk insert query
     * If bulk is enabled, many rows can be inserted once, but it make the auto increment inoperant
     *
     * /!\ Some connection may limit the number of inserted rows in one bulk query
     *
     * @param bool $flag
     *
     * @return $this
     */
    public function bulk($flag = true);

    /**
     * Execute the insert operation
     *
     * @param mixed $columns Not used : only for compatibility with CommandInterface
     *
     * @return int The number of affected rows
     */
    public function execute($columns = null);
}
