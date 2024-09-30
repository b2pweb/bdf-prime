<?php

namespace Bdf\Prime\Query\Contract;

/**
 * Base type for queries which can be compiled to SQL
 *
 * Note: The method {@see Compilable::compile()} will not necessarily return an SQL string, but can return any connection specific object,
 *       on `toSql` method is required to get the SQL string
 */
interface SqlCompilable extends Compilable
{
    /**
     * Get the SQL string of the query
     * If the query is not yet compiled, it will be compiled before getting the SQL
     *
     * The returned string is the raw string passed to the database, so it may contain placeholders
     *
     * @return string|null|false The SQL string, or null if the SQL string cannot be generated
     * @todo remove false return type in prime 3.0, and add a typehint ?string
     */
    public function toSql()/*: ?string*/;
}
