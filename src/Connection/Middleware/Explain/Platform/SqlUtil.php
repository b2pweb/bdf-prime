<?php

namespace Bdf\Prime\Connection\Middleware\Explain\Platform;

use function in_array;
use function preg_split;
use function strtoupper;
use function substr;
use function trim;

/**
 * Utility class for extract SQL information
 */
final class SqlUtil
{
    private const TABLE_LIST_START_KEYWORDS = ['FROM', 'JOIN', 'UPDATE'];
    private const TABLE_LIST_END_KEYWORDS = ['WHERE', 'GROUP', 'ORDER', 'LIMIT', 'OFFSET', 'SELECT', 'HAVING', 'FOR', 'WINDOW', 'PARTITION', 'ON'];

    /**
     * Extract all tables with their alias from the given SQL query
     *
     * The result is an array of alias => table
     * If the table has no alias, the key and the value are the table name
     *
     * @param string $query The SQL query to extract
     *
     * @return array<string, string> Map of alias => table
     */
    public static function tables(string $query): array
    {
        $parts = preg_split('/\s|([,()])/', $query, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE); // Split by space or comma, but keep the comma as a part
        $aliases = [];

        $inTableList = false; // true if in a clause which define a table list (FROM, JOIN...)
        $currentTable = null; // The current table name, before the alias
        $nestedLevel = 0; // The current nested level (for subqueries). If > 0, we are in a subquery, so we ignore the parsing until the end of the subquery
        $nestedQuery = ''; // The current subquery buffer

        foreach ($parts as $part) {
            $word = strtoupper($part);

            // Start of a subquery or nested expression (compare the first character because we do not split parts with parenthesis)
            if ($word === '(') {
                ++$nestedLevel;
                $nestedQuery .= $part;
                continue;
            }

            // We are in subquery or expression, so we ignore the parsing until the end of the subquery
            if ($nestedLevel > 0) {
                $nestedQuery .= ' ' . $part;

                // End of a subquery or nested expression (compare the first character because we do not split parts with parenthesis)
                if ($word === ')') {
                    // We parse the subquery to extract tables once the closing parenthesis is reached
                    if (--$nestedLevel === 0) {
                        // Remove outer parenthesis
                        $aliases += self::tables(trim(substr($nestedQuery, 1, -1)));
                        $nestedQuery = '';
                    }
                }

                continue;
            }

            if (in_array($word, self::TABLE_LIST_START_KEYWORDS)) {
                $inTableList = true;

                // May occurs when switching from "FROM" to "JOIN" for example
                if ($currentTable !== null) {
                    $aliases[$currentTable] = $currentTable;
                    $currentTable = null;
                }

                continue;
            }

            // End of the table list, we save the current table if any
            if (in_array($word, self::TABLE_LIST_END_KEYWORDS)) {
                $inTableList = false;

                if ($currentTable !== null) {
                    $aliases[$currentTable] = $currentTable;
                    $currentTable = null;
                }

                continue;
            }

            if (!$inTableList) {
                continue;
            }

            // AS keyword is optional, so we ignore it
            if ($word === 'AS') {
                continue;
            }

            // Next table expression
            if ($word === ',') {
                if ($currentTable !== null) {
                    $aliases[$currentTable] = $currentTable;
                    $currentTable = null;
                }

                continue;
            }

            if ($currentTable === null) {
                $currentTable = $part;
                continue;
            }

            // A table is already defined, so the current part is the alias
            $aliases[$part] = $currentTable;
            $currentTable = null;
        }

        // If the query ends with a table name, we add it to the aliases (ex: "SELECT * FROM table")
        if ($currentTable !== null) {
            $aliases[$currentTable] = $currentTable;
        }

        return $aliases;
    }
}
