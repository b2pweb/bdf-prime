<?php

namespace Bdf\Prime\Query\Contract;

/**
 * Interface for build bulk insert or replace queries
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
interface BulkWriteBuilderInterface
{
    public const MODE_INSERT = 'insert';
    public const MODE_REPLACE = 'replace';
    public const MODE_IGNORE = 'ignore';

    /**
     * Set the table name
     *
     * @param string $table The table name
     *
     * @return $this
     */
    public function into(string $table);

    /**
     * Set the insert columns and types
     *
     * <code>
     * // Let Prime resolve types
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
     * @param string[] $columns The columns. For typed columns, set the column name as key and type as value
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
     *     ->into('persorn')
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
     * @param array<string,mixed> $data Data to insert, with key as column and value as insert value
     * @param bool $replace Overrides old values, or append value on the bulk insert. This parameter has effect only on bulk insert
     *
     * @return $this
     */
    public function values(array $data, bool $replace = false);

    /**
     * Change the insert mode
     * Prefer use ignore() or replace() methods
     *
     * @param BulkWriteBuilderInterface::MODE_* $mode One of the BulkWriteInterface::MODE_* constants
     *
     * @return $this
     *
     * @see InsertQueryInterface::ignore()
     * @see InsertQueryInterface::replace()
     */
    public function mode(string $mode);

    /**
     * Ignore the insert if the primary constraint failed
     *
     * @param bool $flag true to enable, or false to use normal insert
     *
     * @return $this
     */
    public function ignore(bool $flag = true);

    /**
     * Force replace data :
     * - If the row does not exists, it'll be inserted
     * - If the row exists, it'll be replaced
     *
     * @param bool $flag true to enable, or false to use normal insert
     *
     * @return $this
     */
    public function replace(bool $flag = true);

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
    public function bulk(bool $flag = true);
}
