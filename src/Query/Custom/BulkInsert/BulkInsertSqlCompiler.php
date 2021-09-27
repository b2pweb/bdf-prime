<?php

namespace Bdf\Prime\Query\Custom\BulkInsert;

use Bdf\Prime\Exception\PrimeException;
use Bdf\Prime\Query\CompilableClause;
use Bdf\Prime\Query\Compiler\AbstractCompiler;
use Bdf\Prime\Types\TypeInterface;

/**
 * Compiler for @see BulkInsertQuery
 *
 * The query will be compiled into a prepared statement
 *
 * @extends AbstractCompiler<BulkInsertQuery, \Doctrine\DBAL\Connection&\Bdf\Prime\Connection\ConnectionInterface>
 */
class BulkInsertSqlCompiler extends AbstractCompiler
{
    /**
     * {@inheritdoc}
     */
    protected function doCompileInsert(CompilableClause $query)
    {
        $sql = $this->compileMode($query).' INTO '.$this->quoteIdentifier($query, $query->statements['table']);

        if (!isset($query->state()->compiledParts['columns'])) {
            $this->compileColumns($query);
        }

        $sql .= $query->state()->compiledParts['columns']['sql'].$this->compileValues($query);

        return $this->connection->prepare($sql);
    }

    /**
     * {@inheritdoc}
     */
    protected function doCompileUpdate(CompilableClause $query)
    {
        throw new \BadMethodCallException();
    }

    /**
     * {@inheritdoc}
     */
    protected function doCompileDelete(CompilableClause $query)
    {
        throw new \BadMethodCallException();
    }

    /**
     * {@inheritdoc}
     */
    protected function doCompileSelect(CompilableClause $query)
    {
        throw new \BadMethodCallException();
    }

    /**
     * {@inheritdoc}
     */
    public function quoteIdentifier(CompilableClause $query, $column)
    {
        if (!$query->isQuoteIdentifier()) {
            return $column;
        }

        return $this->platform()->grammar()->quoteIdentifier($column);
    }

    /**
     * {@inheritdoc}
     */
    public function getBindings(CompilableClause $query)
    {
        if ($query->statements['bulk']) {
            $bindings = [];

            foreach ($query->statements['values'] as $values) {
                foreach ($query->state()->compiledParts['columns']['types'] as $field => $type) {
                    $bindings[] = $this->platform()->types()->toDatabase($values[$field] ?? null, $type);
                }
            }

            return $bindings;
        }

        $values = $query->statements['values'][0];
        $bindings = [];

        foreach ($query->state()->compiledParts['columns']['types'] as $field => $type) {
            $bindings[] = $this->platform()->types()->toDatabase($values[$field] ?? null, $type);
        }

        return $bindings;
    }

    /**
     * Compile the INSERT mode
     *
     * @param CompilableClause $query
     *
     * @return string
     * @throws PrimeException
     */
    private function compileMode(CompilableClause $query)
    {
        switch ($query->statements['mode']) {
            case BulkInsertQuery::MODE_REPLACE:
                return 'REPLACE';

            case BulkInsertQuery::MODE_IGNORE:
                if ($this->platform()->grammar()->getName() === 'sqlite') {
                    return 'INSERT OR IGNORE';
                } else {
                    return 'INSERT IGNORE';
                }
                break;
        }

        return 'INSERT';
    }

    /**
     * Compile columns, and resolve types
     *
     * @param CompilableClause $query
     * @throws PrimeException
     */
    private function compileColumns(CompilableClause $query)
    {
        $columns = [];
        $types = [];

        foreach ($query->statements['columns'] as $column) {
            if (!empty($column['type'])) {
                $types[$column['name']] = $column['type'];
                $type = null;
            } else {
                $types[$column['name']] = null;
                $type = true;
            }

            $columns[] = $this->quoteIdentifier($query, $query->preprocessor()->field($column['name'], $type));

            if ($type instanceof TypeInterface) {
                $types[$column['name']] = $type;
            }
        }

        $query->state()->compiledParts['columns'] = [
            'sql'    => '('.implode(', ', $columns).') ',
            'types'  => $types,
            'values' => '('.str_repeat('?, ', count($types) - 1).'?)',
        ];
    }

    /**
     * Compile values for bulk INSERT query
     *
     * @param CompilableClause $query
     *
     * @return string
     */
    private function compileValues(CompilableClause $query)
    {
        if (!$query->statements['bulk']) {
            return 'VALUES '.$query->state()->compiledParts['columns']['values'];
        }

        $values = $query->state()->compiledParts['columns']['values'];

        return 'VALUES '.str_repeat($values.', ', count($query->statements['values']) - 1).$values;
    }
}
