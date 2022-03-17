<?php

namespace Bdf\Prime\Query\Custom\KeyValue;

use Bdf\Prime\Exception\PrimeException;
use Bdf\Prime\Query\CompilableClause;
use Bdf\Prime\Query\Compiler\AbstractCompiler;
use Bdf\Prime\Query\Compiler\QuoteCompilerInterface;
use Bdf\Prime\Query\Contract\Compilable;
use Bdf\Prime\Query\Expression\ExpressionInterface;
use Doctrine\DBAL\Statement;

/**
 * SQL compiler for KeyValueQuery
 *
 * @extends AbstractCompiler<KeyValueQuery, \Doctrine\DBAL\Connection&\Bdf\Prime\Connection\ConnectionInterface>
 * @implements QuoteCompilerInterface<KeyValueQuery>
 */
class KeyValueSqlCompiler extends AbstractCompiler implements QuoteCompilerInterface
{
    /**
     * {@inheritdoc}
     */
    protected function doCompileInsert(CompilableClause $query)
    {
        throw new \BadMethodCallException('INSERT operation is not supported on key value query');
    }

    /**
     * {@inheritdoc}
     */
    protected function doCompileUpdate(CompilableClause $query)
    {
        return $this->prepare($query, 'UPDATE '.$this->quoteIdentifier($query, $query->statements['table']).$this->compileValues($query).$this->compileWhere($query));
    }

    /**
     * {@inheritdoc}
     */
    protected function doCompileDelete(CompilableClause $query)
    {
        return $this->prepare($query, 'DELETE FROM '.$this->quoteIdentifier($query, $query->statements['table']).$this->compileWhere($query));
    }

    /**
     * {@inheritdoc}
     */
    protected function doCompileSelect(CompilableClause $query)
    {
        $sql = 'SELECT '.$this->compileProjection($query).' FROM '.$this->quoteIdentifier($query, $query->statements['table']).$this->compileWhere($query).$this->compileLimit($query);

        return $this->prepare($query, $sql);
    }

    /**
     * {@inheritdoc}
     */
    public function quoteIdentifier(CompilableClause $query, string $column): string
    {
        return $query->isQuoteIdentifier()
            ? $this->platform()->grammar()->quoteIdentifier($column)
            : $column
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function quote($value)
    {
        return $this->connection->quote($value);
    }

    /**
     * {@inheritdoc}
     */
    public function getBindings(CompilableClause $query): array
    {
        $bindings = [];

        if ($query->type() === Compilable::TYPE_UPDATE) {
            foreach ($query->statements['values']['data'] as $column => $value) {
                $bindings[] = $this->platform()->types()->toDatabase($value, $query->state()->compiledParts['values']['types'][$column] ?? null);
            }
        }

        foreach ($query->statements['where'] as $field => $value) {
            $bindings[] = $this->platform()->types()->toDatabase($value, $query->state()->compiledParts['types'][$field] ?? null);
        }

        if ($query->type() === Compilable::TYPE_SELECT && isset($query->statements['limit']) && isset($query->statements['offset'])) {
            $bindings[] = $query->statements['limit'];
            $bindings[] = $query->statements['offset'];
        }

        return $bindings;
    }

    /**
     * Compile the primary key condition
     *
     * @param CompilableClause $query
     *
     * @return string
     * @throws PrimeException
     */
    private function compileWhere(CompilableClause $query)
    {
        if (isset($query->state()->compiledParts['where'])) {
            return $query->state()->compiledParts['where'];
        }

        if (empty($query->statements['where'])) {
            $query->state()->compiledParts['types'] = [];
            $query->state()->compiledParts['where'] = '';

            return '';
        }

        $sql = [];
        $types = [];

        foreach ($query->statements['where'] as $field => $value) {
            $type = true;

            $column = $query->preprocessor()->field($field, $type);

            $sql[] = $this->quoteIdentifier($query, $column).' = ?';

            if ($type !== true) {
                $types[$field] = $type;
            }
        }

        $query->state()->compiledParts['types'] = $types;

        return $query->state()->compiledParts['where'] = ' WHERE '.implode(' AND ', $sql);
    }

    /**
     * Compile the projection columns
     *
     * @param CompilableClause $query
     *
     * @return string
     * @throws PrimeException
     */
    private function compileProjection(CompilableClause $query)
    {
        if (!empty($query->statements['aggregate'])) {
            return $this->compileAggregate($query, $query->statements['aggregate'][0], $query->statements['aggregate'][1]);
        }

        if (empty($query->statements['columns'])) {
            return '*';
        }

        $sql = [];

        foreach ($query->statements['columns'] as $column) {
            $sql[] = $this->compileExpressionColumn($query, $column['column'], $column['alias']);
        }

        return implode(', ', $sql);
    }

    /**
     * Compile a SQL function
     *
     * @param CompilableClause $query
     * @param string $function  The sql function
     * @param string $column    The column to aggregate
     *
     * @return string
     * @throws PrimeException
     */
    private function compileAggregate(CompilableClause $query, $function, $column)
    {
        if ($column !== '*') {
            $column = $query->preprocessor()->field($column);
            $column = $this->quoteIdentifier($query, $column);
        }

        switch ($function) {
            case 'avg'  :      return $this->platform()->grammar()->getAvgExpression($column).' AS aggregate';
            case 'count':      return $this->platform()->grammar()->getCountExpression($column).' AS aggregate';
            case 'max'  :      return $this->platform()->grammar()->getMaxExpression($column).' AS aggregate';
            case 'min'  :      return $this->platform()->grammar()->getMinExpression($column).' AS aggregate';
            case 'pagination': return $this->platform()->grammar()->getCountExpression($column).' AS aggregate';
            case 'sum'  :      return $this->platform()->grammar()->getSumExpression($column).' AS aggregate';

            default:
                $method = 'get'.ucfirst($function).'Expression';
                return $this->platform()->grammar()->{$method}($column).' AS aggregate';
        }
    }

    /**
     * Compile expression column
     *
     * @param CompilableClause $query
     * @param mixed $column
     * @param string $alias
     *
     * @return string
     * @throws PrimeException
     */
    private function compileExpressionColumn(CompilableClause $query, $column, $alias = null)
    {
        if ($column instanceof ExpressionInterface) {
            return $alias !== null
                ? $column->build($query, $this).' as '.$this->quoteIdentifier($query, $alias)
                : $column->build($query, $this)
            ;
        }

        $column = $query->preprocessor()->field($column);

        if (strpos($column, '*') !== false) {
            return $column;
        }

        return $alias !== null
            ? $this->quoteIdentifier($query, $column).' as '.$this->quoteIdentifier($query, $alias)
            : $this->quoteIdentifier($query, $column)
        ;
    }

    /**
     * Compile the LIMIT clause, using placeholder for pagination
     * /!\ Compatible only with SGBD which supports LIMIT and OFFSET keyword (MySQL, SQLite and PgSQL)
     *
     * @param CompilableClause $query
     *
     * @return string
     * @throws PrimeException
     */
    private function compileLimit(CompilableClause $query)
    {
        if (!isset($query->statements['limit']) && !isset($query->statements['offset'])) {
            return '';
        }

        if (!isset($query->statements['offset'])) {
            return ' LIMIT '.$query->statements['limit'];
        }

        if (!isset($query->statements['limit'])) {
            switch ($this->platform()->name()) {
                case 'sqlite':
                    return ' LIMIT -1 OFFSET '.$query->statements['offset'];

                case 'mysql':
                    return ' LIMIT 18446744073709551615 OFFSET '.$query->statements['offset'];

                default:
                    return ' OFFSET '.$query->statements['offset'];
            }
        }

        // Use prepared only for pagination
        return ' LIMIT ? OFFSET ?';
    }

    /**
     * Compile SET clause on UPDATE query
     *
     * @param CompilableClause $query
     *
     * @return string
     * @throws PrimeException
     */
    private function compileValues(CompilableClause $query): string
    {
        if (isset($query->state()->compiledParts['values'])) {
            return $query->state()->compiledParts['values']['sql'];
        }

        $types = $query->statements['values']['types'];
        $sql = null;

        foreach ($query->statements['values']['data'] as $column => $value) {
            $type = $types[$column] ?? true;

            if ($sql === null) {
                $sql = ' SET ';
            } else {
                $sql .= ', ';
            }

            $sql .= $this->quoteIdentifier($query, $query->preprocessor()->field($column, $type)).' = ?';

            if ($type !== true) {
                $types[$column] = $type;
            }
        }

        $query->state()->compiledParts['values'] = [
            'sql'   => $sql,
            'types' => $types
        ];

        return (string) $sql;
    }

    /**
     * Prepare the SQL statement
     *
     * @param CompilableClause $query
     * @param string $sql
     *
     * @return Statement
     */
    private function prepare(CompilableClause $query, $sql)
    {
        $query->state()->compiledParts['sql'] = $sql;

        if (isset($query->state()->compiledParts['prepared'][$sql])) {
            return $query->state()->compiledParts['prepared'][$sql];
        }

        return $query->state()->compiledParts['prepared'][$sql] = $this->connection->prepare($sql);
    }
}
