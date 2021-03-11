<?php

namespace Bdf\Prime\Query\Compiler;

use Bdf\Prime\Exception\PrimeException;
use Bdf\Prime\Exception\QueryException;
use Bdf\Prime\Query\CommandInterface;
use Bdf\Prime\Query\CompilableClause;
use Bdf\Prime\Query\Contract\Compilable;
use Bdf\Prime\Query\Expression\ExpressionInterface;
use Bdf\Prime\Query\Expression\ExpressionTransformerInterface;
use Bdf\Prime\Query\Query;
use Bdf\Prime\Query\QueryInterface;
use Bdf\Prime\Query\SqlQueryInterface;
use Bdf\Prime\Types\TypeInterface;
use Doctrine\DBAL\LockMode;
use Doctrine\DBAL\Query\Expression\CompositeExpression;
use UnexpectedValueException;

/**
 * Base compiler for SQL queries
 *
 * @extends AbstractCompiler<\Bdf\Prime\Query\SqlQueryInterface&CompilableClause>
 */
class SqlCompiler extends AbstractCompiler
{
    /**
     * Quote a value
     * 
     * @param mixed $value
     *
     * @return string
     * @throws PrimeException
     */
    public function quote($value)
    {
        return $this->connection->quote($this->autoConvertValue($value));
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
     * Quote a identifier on multiple columns
     *
     * @param SqlQueryInterface&CompilableClause $query
     * @param array $columns
     *
     * @return array
     * @throws PrimeException
     */
    public function quoteIdentifiers(CompilableClause $query, array $columns)
    {
        if (!$query->isQuoteIdentifier()) {
            return $columns;
        }

        return array_map([$this->platform()->grammar(), 'quoteIdentifier'], $columns);
    }
    
    /**
     * {@inheritdoc}
     */
    protected function doCompileInsert(CompilableClause $query)
    {
        $query->state()->currentPart = 0;

        if ($query->statements['ignore'] && $this->platform()->grammar()->getReservedKeywordsList()->isKeyword('IGNORE')) {
            if ($this->platform()->grammar()->getName() === 'sqlite') {
                $insert = 'INSERT OR IGNORE INTO ';
            } else {
                $insert = 'INSERT IGNORE INTO ';
            }
        } elseif ($query->statements['replace'] && $this->platform()->grammar()->getReservedKeywordsList()->isKeyword('REPLACE')) {
            $insert = 'REPLACE INTO ';
        } else {
            $insert = 'INSERT INTO ';
        }

        foreach ($query->statements['tables'] as $table) {
            return $query->state()->compiled = $insert.$this->quoteIdentifier($query, $table['table']).$this->compileInsertData($query);
        }

        throw new QueryException('The insert table name is missing');
    }

    /**
     * Compile the data part of the insert query
     *
     * @param SqlQueryInterface&CompilableClause $query
     *
     * @return string
     * @throws PrimeException
     */
    protected function compileInsertData(CompilableClause $query)
    {
        // @todo Do not use QueryInterface
        if ($query->statements['values']['data'] instanceof QueryInterface) {
            return $this->compileInsertSelect($query);
        }

        list($columns, $values) = $this->compileInsertValues($query);

        return ' ('.implode(', ', $columns).') VALUES('.implode(', ', $values).')';
    }

    /**
     * Compile an INSERT INTO ... SELECT ... query
     *
     * @param SqlQueryInterface&CompilableClause $query
     *
     * @return string
     * @throws PrimeException
     */
    protected function compileInsertSelect(CompilableClause $query)
    {
        /** @var Query $select */
        $select = clone $query->statements['values']['data']; // Clone the query for ensure that it'll not be modified
        $columns = [];

        // Columns are defined on the select query
        // Alias of the selected columns will be concidered as the INSERT table columns
        if ($select->statements['columns'] && $select->statements['columns'][0]['column'] !== '*') {
            foreach ($select->statements['columns'] as &$column) {
                $alias = $query->preprocessor()->field($column['alias'] ?? $column['column']);

                // Modify the column alias to match with the INSERT column
                $column['alias'] = $alias;

                $columns[] = $this->quoteIdentifier($query, $alias);
            }
        }

        $sql = ' '.$this->compileSelect($select); // @todo Ensure that the query is sql compilable
        $this->addQueryBindings($query, $select);

        return empty($columns) ? $sql : ' ('.implode(', ', $columns).')'.$sql;
    }

    /**
     * Compile columns and values to insert
     *
     * @param SqlQueryInterface&CompilableClause $query
     *
     * @return array
     * @throws PrimeException
     */
    protected function compileInsertValues(CompilableClause $query)
    {
        $data = $query->statements['values'];

        $columns = [];
        $values = [];

        foreach ($data['data'] as $column => $value) {
            $type = $data['types'][$column] ?? true;
            $column = $query->preprocessor()->field($column, $type);

            // The type cannot be resolved by preprocessor
            if ($type === true) {
                $type = null;
            }

            $columns[] = $this->quoteIdentifier($query, $column);
            $values[]  = $this->compileTypedValue($query, $value, $type);
        }

        return [$columns, $values];
    }

    /**
     * {@inheritdoc}
     */
    protected function doCompileUpdate(CompilableClause $query)
    {
        $query->state()->currentPart = 0;

        $values = $this->compileUpdateValues($query);

        foreach ($query->statements['tables'] as $table) {
            return $query->state()->compiled = 'UPDATE '
                . $this->quoteIdentifier($query, $table['table'])
                . ' SET ' . implode(', ', $values)
                . $this->compileWhere($query)
            ;
        }

        throw new QueryException('The update table name is missing');
    }

    /**
     * Compile columns and values to update
     *
     * @param SqlQueryInterface&CompilableClause $query
     *
     * @return array
     * @throws PrimeException
     */
    protected function compileUpdateValues(CompilableClause $query)
    {
        $data = $query->statements['values'];
        $values = [];

        foreach ($data['data'] as $column => $value) {
            $type = $data['types'][$column] ?? true;
            $column = $query->preprocessor()->field($column, $type);

            $values[] = $this->quoteIdentifier($query, $column)
                . ' = '
                . $this->compileTypedValue($query, $value, $type);
        }

        return $values;
    }

    /**
     * {@inheritdoc}
     */
    protected function doCompileDelete(CompilableClause $query)
    {
        $query->state()->currentPart = 0;

        foreach ($query->statements['tables'] as $table) {
            return $query->state()->compiled = 'DELETE FROM '
                . $this->quoteIdentifier($query, $table['table'])
                . $this->compileWhere($query)
            ;
        }

        throw new QueryException('The delete table name is missing');
    }
    
    /**
     * {@inheritdoc}
     */
    protected function doCompileSelect(CompilableClause $query)
    {
        if ($this->isComplexAggregate($query)) {
            return $query->state()->compiled = $this->compileComplexAggregate($query);
        }

        if (!isset($query->state()->compiledParts['columns'])) {
            $query->state()->currentPart = 'columns';
            $query->state()->compiledParts['columns'] = $this->compileColumns($query);
        }

        if (!isset($query->state()->compiledParts['from'])) {
            $query->state()->compiledParts['from'] = $this->compileFrom($query);
        }

        if (!isset($query->state()->compiledParts['groups'])) {
            $query->state()->currentPart = 'groups';
            $query->state()->compiledParts['groups'] = $this->compileGroup($query);
        }

        if (!isset($query->state()->compiledParts['having'])) {
            $query->state()->currentPart = 'having';
            $query->state()->compiledParts['having'] = $this->compileHaving($query);
        }

        if (!isset($query->state()->compiledParts['orders'])) {
            $query->state()->currentPart = 'orders';
            $query->state()->compiledParts['orders'] = $this->compileOrder($query);
        }

        if (!isset($query->state()->compiledParts['where'])) {
            $query->state()->currentPart = 'where';
            $query->state()->compiledParts['where'] = $this->compileWhere($query);
        }

        if (!isset($query->state()->compiledParts['joins'])) {
            $query->state()->currentPart = 'joins';
            $query->state()->compiledParts['joins'] = $this->compileJoins($query);
        }

        if (!isset($query->state()->compiledParts['lock'])) {
            $query->state()->currentPart = 'lock';
            $query->state()->compiledParts['lock'] = $this->compileLock($query);
        }

        $sql = $query->state()->compiledParts['columns']
                .$query->state()->compiledParts['from']
                .$query->state()->compiledParts['joins']
                .$query->state()->compiledParts['where']
                .$query->state()->compiledParts['groups']
                .$query->state()->compiledParts['having']
                .$query->state()->compiledParts['orders'];

        if ($query->isLimitQuery()) {
            $sql = $this->platform()->grammar()->modifyLimitQuery($sql, $query->statements['limit'], $query->statements['offset']);
        }

        return $query->state()->compiled = $sql.$query->state()->compiledParts['lock'];
    }

    /**
     * Check if the the query is an aggregate which requires to execute the query as temporary table
     * A temporary table is required for DISTINT aggregate with wildcard "*" column
     *
     * @param SqlQueryInterface&CompilableClause $query
     *
     * @return bool
     */
    protected function isComplexAggregate(CompilableClause $query)
    {
        return isset($query->statements['aggregate']) && $query->statements['aggregate'][1] === '*' && $query->statements['distinct'];
    }

    /**
     * Compile the complexe aggregate query
     * Will generate a query in form : "SELECT [aggregate](*) FROM ([query])"
     *
     * @param SqlQueryInterface&CompilableClause $query
     *
     * @return string
     * @throws PrimeException
     */
    protected function compileComplexAggregate(CompilableClause $query)
    {
        list($function, $column) = $query->statements['aggregate'];

        $query->statements['aggregate'] = null;
        $query->statements['columns'] = $column === '*' ? [] : [['column' => $column, 'alias' => null]];

        return 'SELECT '.$this->compileAggregate($query, $function, '*', false).' FROM ('.$this->doCompileSelect($query).') as derived_query';
    }

    /**
     * @param SqlQueryInterface&CompilableClause $query
     *
     * @return string
     * @throws PrimeException
     */
    protected function compileColumns(CompilableClause $query)
    {
        if (!empty($query->statements['aggregate'])) {
            return 'SELECT '.$this->compileAggregate($query, $query->statements['aggregate'][0], $query->statements['aggregate'][1], $query->statements['distinct']);
        }

        if ($query->statements['distinct'] && $this->platform()->grammar()->getReservedKeywordsList()->isKeyword('DISTINCT')) {
            $select = 'SELECT DISTINCT ';
        } else {
            $select = 'SELECT ';
        }
        
        if (empty($query->statements['columns'])) {
            $root = $query->preprocessor()->root();

            if ($root) {
                $select .= $this->quoteIdentifier($query, $root).'.';
            }

            return $select.'*';
        }

        $sql = [];
        
        foreach ($query->statements['columns'] as $column) {
            $sql[] = $this->compileExpressionColumn($query, $column['column'], $column['alias']);
        }
        
        return $select.implode(', ', $sql);
    }

    /**
     * Compile a SQL function
     *
     * @param SqlQueryInterface&CompilableClause $query
     * @param string $function  The sql function
     * @param string $column    The column to aggregate
     * @param bool   $distinct  The distinct status
     *
     * @return string
     * @throws PrimeException
     */
    protected function compileAggregate(CompilableClause $query, $function, $column, $distinct)
    {
        if ($column !== '*') {
            $column = $query->preprocessor()->field($column);
            $column = $this->quoteIdentifier($query, $column);

            if ($distinct && $this->platform()->grammar()->getReservedKeywordsList()->isKeyword('DISTINCT')) {
                // Le count ne compte pas les fields qui ont une valeur NULL.
                // Pour une pagination, il est important de compter les valeurs null sachant qu'elles seront sélectionnées.
                // La pagination utilise une column que pour le distinct.
                if ($function === 'pagination') {
                    $column = 'IFNULL('.$column.',"___null___")';
                }

                $column = 'DISTINCT '.$column;
            }
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
     * @param SqlQueryInterface&CompilableClause $query
     * @param mixed $column
     * @param string $alias
     * 
     * @return string
     * @throws PrimeException
     */
    protected function compileExpressionColumn(CompilableClause $query, $column, $alias = null)
    {
        if ($column instanceof QueryInterface) {
            return $this->compileSubQuery($query, $column, $alias);
        }
        
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
     * @param SqlQueryInterface&CompilableClause $query
     *
     * @return string
     * @throws PrimeException
     */
    protected function compileFrom(CompilableClause $query)
    {
        $sql = ' FROM ';
        $isFirst = true;

        // Loop through all FROM clauses
        foreach ($this->compileTableAndAliasClause($query, $query->statements['tables']) as $from) {
            if (!$isFirst) {
                $sql .= ', ';
            } else {
                $isFirst = false;
            }

            $sql .= $from['sql'];
        }

        return $sql;
    }

    /**
     * @param SqlQueryInterface&CompilableClause $query
     *
     * @return string
     * @throws PrimeException
     */
    protected function compileJoins(CompilableClause $query)
    {
        if (empty($query->statements['joins'])) {
            return '';
        }

        $sql = '';

        foreach ($this->compileTableAndAliasClause($query, $query->statements['joins']) as $join) {
            $sql .= ' '.$join['type'].' JOIN '.$join['sql'].' ON '.$this->compileCompilableClauses($query, $join['on']);
        }

        return $sql;
    }

    /**
     * Compile clauses with 'table' and 'alias' keys
     *
     * The table name will be resolved, quoted, and generate the alias if present
     * Duplicate table name or aliases will also be removed from result
     *
     * The compiled table expression will be returned into the 'sql' key
     * All input parameter will be kept on the return value
     *
     * @param SqlQueryInterface&CompilableClause $query
     * @param array $clauses
     *
     * @return array
     *
     * @throws PrimeException
     */
    protected function compileTableAndAliasClause(CompilableClause $query, array $clauses): array
    {
        $databasePrefix = $this->getDatabaseNamePrefix($query);
        $compiled = [];

        foreach ($clauses as $from) {
            if ($from['table'] instanceof QueryInterface) {
                $from['sql'] = $this->compileSubQuery($query, $from['table'], $from['alias']);
                $compiled[] = $from;
            } else {
                $from = $query->preprocessor()->table($from);

                if ($from['alias'] === null) {
                    $from['sql'] = $this->quoteIdentifier($query, $databasePrefix.$from['table']);
                    $compiled[$from['table']] = $from;
                } else {
                    $from['sql'] = $this->quoteIdentifier($query, $databasePrefix.$from['table']) . ' ' . $this->quoteIdentifier($query, $from['alias']);
                    $compiled[$from['alias']] = $from;
                }
            }
        }

        return $compiled;
    }

    /**
     * Adding database prefix for sub query x-db
     *
     * @param SqlQueryInterface&CompilableClause $query
     *
     * @return string
     */
    protected function getDatabaseNamePrefix(CompilableClause $query): string
    {
        if ($query instanceof CommandInterface && $query->compiler() !== $this && $query->connection()->getDatabase() !== $this->connection->getDatabase()) {
            return $query->connection()->getDatabase().'.';
        }

        return '';
    }

    /**
     * Compile Where sql
     * 
     * @param SqlQueryInterface&CompilableClause $query
     * 
     * @return string
     * @throws PrimeException
     */
    protected function compileWhere(CompilableClause $query)
    {
        if (empty($query->statements['where'])) {
            return '';
        }
        
        return ' WHERE '.$this->compileCompilableClauses($query, $query->statements['where']);
    }

    /**
     * Compile having sql
     * 
     * @param SqlQueryInterface&CompilableClause $query
     * 
     * @return string
     * @throws PrimeException
     */
    protected function compileHaving(CompilableClause $query)
    {
        if (empty($query->statements['having'])) {
            return '';
        }
        
        return ' HAVING '.$this->compileCompilableClauses($query, $query->statements['having']);
    }

    /**
     * @param SqlQueryInterface&CompilableClause $query
     * @param array $clauses
     *
     * @return string
     * @throws PrimeException
     */
    protected function compileCompilableClauses(CompilableClause $query, array &$clauses)
    {
        $sql = [];
        $i = 0;

        // Permet de retirer le niveau du nested
        if (count($clauses) === 1 && isset($clauses[0]['nested'])) {
            $result = $this->compileCompilableClauses($query, $clauses[0]['nested']);
            /*
             * We check he if where expression has added constraints (from relation).
             * If we still have one clause, we return the compiled sql
             * Otherwise we start the loop of clauses.
             */
            if (count($clauses) === 1) {
                return $result;
            }

            // Add the nested level
            $sql[] = '('.$result.')';
            $i = 1;
        }

        $clauses[0]['glue'] = null;

        //Cannot use foreach because where expression can add new relations with constraints
        for (; isset($clauses[$i]); ++$i) {
            $part = $clauses[$i];

            if ($part['glue'] !== null) {
                $part['glue'] .= ' ';
            }

            $part = $query->preprocessor()->expression($part);
            
            if (isset($part['nested'])) {
                $sql[] = $part['glue'].'('.$this->compileCompilableClauses($query, $part['nested']).')';
            } elseif (!isset($part['raw'])) {
                $sql[] = $part['glue'].$this->compileExpression($query, $part['column'], $part['operator'], $part['value'], $part['converted'] ?? false);
            } else {
                $sql[] = $part['glue'].$this->compileRawValue($query, $part['raw']);
            }
        }
        
        return implode(' ', $sql);
    }

    /**
     * Determine which operator to use based on custom and standard syntax
     *
     * @param SqlQueryInterface&CompilableClause $query
     * @param string $column
     * @param string $operator
     * @param mixed  $value
     * @param bool $converted
     * 
     * @return string  operator found
     * 
     * @throws UnexpectedValueException
     * @throws PrimeException
     */
    protected function compileExpression(CompilableClause $query, string $column, string $operator, $value, bool $converted): string
    {
        if ($value instanceof ExpressionTransformerInterface) {
            /** @psalm-suppress InvalidArgument */
            $value->setContext($this, $column, $operator);

            $column    = $value->getColumn();
            $operator  = $value->getOperator();
            $value     = $value->getValue();
            $converted = true;
        }

        switch ($operator) {
            case '<':
            case ':lt':
                if (is_array($value)) {
                    return $this->compileIntoExpression($query, $value, $column, '<', $converted);
                }
                return $this->quoteIdentifier($query, $column).' < '.$this->compileExpressionValue($query, $value, $converted);

            case '<=':
            case ':lte':
                if (is_array($value)) {
                    return $this->compileIntoExpression($query, $value, $column, '<=', $converted);
                }
                return $this->quoteIdentifier($query, $column).' <= '.$this->compileExpressionValue($query, $value, $converted);

            case '>':
            case ':gt':
                if (is_array($value)) {
                    return $this->compileIntoExpression($query, $value, $column, '>', $converted);
                }
                return $this->quoteIdentifier($query, $column).' > '.$this->compileExpressionValue($query, $value, $converted);

            case '>=':
            case ':gte':
                if (is_array($value)) {
                    return $this->compileIntoExpression($query, $value, $column, '>=', $converted);
                }
                return $this->quoteIdentifier($query, $column).' >= '.$this->compileExpressionValue($query, $value, $converted);

            // REGEX matching
            case '~=':
            case '=~':
            case ':regex':
                if (is_array($value)) {
                    return $this->compileIntoExpression($query, $value, $column, 'REGEXP', $converted);
                }
                return $this->quoteIdentifier($query, $column).' REGEXP '.$this->compileExpressionValue($query, (string)$value, $converted);

            // LIKE
            case ':like':
                if (is_array($value)) {
                    return $this->compileIntoExpression($query, $value, $column, 'LIKE', $converted);
                }
                return $this->quoteIdentifier($query, $column).' LIKE '.$this->compileExpressionValue($query, $value, $converted);

            // NOT LIKE
            case ':notlike':
            case '!like':
                if (is_array($value)) {
                    return $this->compileIntoExpression($query, $value, $column, 'NOT LIKE', $converted, CompositeExpression::TYPE_AND);
                }
                return $this->quoteIdentifier($query, $column).' NOT LIKE '.$this->compileExpressionValue($query, $value, $converted);

            // In
            case 'in':
            case ':in':
                if (empty($value)) {
                    return $this->platform()->grammar()->getIsNullExpression($this->quoteIdentifier($query, $column));
                }
                return $this->compileInExpression($query, $value, $column, 'IN', $converted);

            // Not in
            case 'notin':
            case '!in':
            case ':notin':
                if (empty($value)) {
                    return $this->platform()->grammar()->getIsNotNullExpression($this->quoteIdentifier($query, $column));
                }
                return $this->compileInExpression($query, $value, $column, 'NOT IN', $converted);

            // Between
            case 'between':
            case ':between':
                if (is_array($value)) {
                    return $this->platform()->grammar()->getBetweenExpression($this->quoteIdentifier($query, $column), $this->compileExpressionValue($query, $value[0], $converted), $this->compileExpressionValue($query, $value[1], $converted));
                }
                return $this->platform()->grammar()->getBetweenExpression($this->quoteIdentifier($query, $column), 0, $this->compileExpressionValue($query, $value, $converted));

            // Not between
            case '!between':
            case ':notbetween':
                return $this->platform()->grammar()->getNotExpression($this->compileExpression($query, $column, ':between', $value, $converted));

            // Not equal
            case '<>':
            case '!=':
            case ':ne':
            case ':not':
                if (is_null($value)) {
                    return $this->platform()->grammar()->getIsNotNullExpression($this->quoteIdentifier($query, $column));
                }
                if (is_array($value)) {
                    return $this->compileExpression($query, $column, ':notin', $value, $converted);
                }
                return $this->quoteIdentifier($query, $column).' != '.$this->compileExpressionValue($query, $value, $converted);

            // Equals
            case '=':
            case ':eq':
                if (is_null($value)) {
                    return $this->platform()->grammar()->getIsNullExpression($this->quoteIdentifier($query, $column));
                }
                if (is_array($value)) {
                    return $this->compileExpression($query, $column, ':in', $value, $converted);
                }
                return $this->quoteIdentifier($query, $column).' = '.$this->compileExpressionValue($query, $value, $converted);
                
            // Unsupported operator
            default:
                throw new UnexpectedValueException("Unsupported operator '" . $operator . "' in WHERE clause");
        }
    }

    /**
     * Compile expression value
     *
     * @param SqlQueryInterface&CompilableClause $query
     * @param mixed $value
     * @param bool $converted Does the value is already converted to database ?
     * 
     * @return string
     * @throws PrimeException
     */
    protected function compileExpressionValue(CompilableClause $query, $value, bool $converted)
    {
        if ($value instanceof QueryInterface) {
            return $this->compileSubQuery($query, $value);
        }

        if ($value instanceof ExpressionInterface) {
            return $value->build($query, $this);
        }

        return $converted ? $this->bindRaw($query, $value) : $this->bindTyped($query, $value, null);
    }

    /**
     * Compile expression value with type
     *
     * @param SqlQueryInterface&CompilableClause $query
     * @param mixed $value
     * @param TypeInterface|null $type The type. If null it will be resolved from value
     *
     * @return string
     * @throws PrimeException
     */
    protected function compileTypedValue(CompilableClause $query, $value, ?TypeInterface $type)
    {
        if ($value instanceof QueryInterface) {
            return $this->compileSubQuery($query, $value);
        }

        if ($value instanceof ExpressionInterface) {
            return $value->build($query, $this);
        }

        return $this->bindTyped($query, $value, $type);
    }

    /**
     * Compile raw expression value
     *
     * @param SqlQueryInterface&CompilableClause $query
     * @param mixed $value
     * 
     * @return string
     * @throws PrimeException
     */
    protected function compileRawValue(CompilableClause $query, $value)
    {
        if ($value instanceof QueryInterface) {
            return $this->compileSubQuery($query, $value);
        }
        
        if ($value instanceof ExpressionInterface) {
            return $value->build($query, $this);
        }
            
        return $value;
    }

    /**
     * Add sub query bindings.
     *
     * @param SqlQueryInterface&CompilableClause $clause
     * @param QueryInterface $query The sub query.
     * @param string $alias
     *
     * @return string  The sub query sql
     * @throws PrimeException
     */
    protected function compileSubQuery(CompilableClause $clause, QueryInterface $query, $alias = null)
    {
        //TODO les alias peuvent etre les memes. Ne gene pas MySQL, voir à regénérer ceux de la subquery
        $sql = '('.$this->compileSelect($query).')';
        
        if ($alias) {
            $sql = $sql . ' as ' . $this->quoteIdentifier($clause, $alias);
        }
        
        $this->addQueryBindings($clause, $query);

        return $sql;
    }
    
    /**
     * Compile IN or NOT IN expression
     *
     * @param SqlQueryInterface&CompilableClause $query
     * @param array|QueryInterface|ExpressionInterface  $values
     * @param string $column
     * @param string $operator
     * @param boolean $converted
     * 
     * @return string
     * @throws PrimeException
     */
    protected function compileInExpression(CompilableClause $query, $values, string $column, string $operator = 'IN', bool $converted = false)
    {
        if (is_array($values)) {
            $hasNullValue = null;
            foreach ($values as $index => &$value) {
                if ($value === null) {
                    unset($values[$index]);
                    $hasNullValue = true;
                } else {
                    $value = $converted ? $this->bindRaw($query, $value) : $this->bindTyped($query, $value, null);
                }
            }

            // If the collection has a null value we add the null expression
            if ($hasNullValue) {
                if ($values) {
                    $expression = '('.$this->quoteIdentifier($query, $column).' '.$operator.' ('.implode(',', $values).')';

                    if ($operator === 'IN') {
                        return $expression.' OR '.$this->compileExpression($query, $column, 'in', null, $converted).')';
                    } else {
                        return $expression.' AND '.$this->compileExpression($query, $column, '!in', null, $converted).')';
                    }
                }

                return $this->compileExpression($query, $column, $operator === 'IN' ? 'in' : '!in', null, $converted);
            }

            $values = '('.implode(',', $values).')';
        } elseif ($values instanceof QueryInterface) {
            $values = $this->compileSubQuery($query, $values);
        } elseif ($values instanceof ExpressionInterface) {
            $values = '('.$values->build($query, $this).')';
        } else {
            $values = $converted ? $this->bindRaw($query, $values) : $this->bindTyped($query, $values, null);
            $values = '('.$values.')'; // @todo utile ?
        }

        return $this->quoteIdentifier($query, $column).' '.$operator.' '.$values;
    }

    /**
     * Compile into expression
     * Multiple OR expression
     *
     * @param SqlQueryInterface&CompilableClause $query
     * @param array $values
     * @param string $column
     * @param string $operator
     * @param bool $converted True if the value is already converted, or false to convert into bind()
     * @param string $separator The expressions separators. By default set to OR, but should be AND on negative (NOT) expressions. See CompositeExpression
     *
     * @return string
     * @throws PrimeException
     */
    public function compileIntoExpression(CompilableClause $query, array $values, $column, $operator, $converted, $separator = CompositeExpression::TYPE_OR)
    {
        $into = [];

        $column = $this->quoteIdentifier($query, $column);

        foreach ($values as $value) {
            $into[] = $column.' '.$operator.' '.$this->compileExpressionValue($query, $value, $converted);
        }

        return '('.implode(' '.$separator.' ', $into).')';
    }

    /**
     * Compile group by expression
     * 
     * @param SqlQueryInterface&CompilableClause $query
     * 
     * @return string
     * @throws PrimeException
     */
    protected function compileGroup(CompilableClause $query)
    {
        if (empty($query->statements['groups'])) {
            return '';
        }

        $fields = array_map([$query->preprocessor(), 'field'], $query->statements['groups']);

        if ($query->isQuoteIdentifier()) {
            $fields = $this->quoteIdentifiers($query, $fields);
        }
        
        return ' GROUP BY '.implode(', ', $fields);
    }

    /**
     * Compile order by expression
     * 
     * @param SqlQueryInterface&CompilableClause $query
     * 
     * @return string
     * @throws PrimeException
     */
    protected function compileOrder(CompilableClause $query)
    {
        if (empty($query->statements['orders'])) {
            return '';
        }
        
        $sql = [];

        foreach ($query->statements['orders'] as $part) {
            if ($part['sort'] instanceof ExpressionInterface) {
                $part['sort'] = $part['sort']->build($query, $this);
            } else {
                $part['sort'] = $this->quoteIdentifier($query, $query->preprocessor()->field($part['sort']));
            }
            
            $sql[] = $part['sort'].' '.$part['order'];
        }

        return ' ORDER BY '.implode(', ', $sql);
    }

    /**
     * Compile the lock expression
     *
     * Does not support system that use hint like SqlServer
     *
     * @param SqlQueryInterface&CompilableClause $query
     *
     * @return string
     * @throws PrimeException
     */
    protected function compileLock(CompilableClause $query)
    {
        $lock = $query->statements['lock'];

        // The lock should not be applied on aggregate function
        if ($lock !== null && !$query->statements['aggregate']) {
            // Lock for update
            if ($lock === LockMode::PESSIMISTIC_WRITE) {
                return ' ' . $this->platform()->grammar()->getWriteLockSQL();
            }

            // Shared Lock: other process can read the row but not update it.
            if ($lock === LockMode::PESSIMISTIC_READ) {
                return ' ' . $this->platform()->grammar()->getReadLockSQL();
            }
        }

        return '';
    }

    /**
     * Add sub query bindings.
     *
     * @param SqlQueryInterface&CompilableClause $clause The main query
     * @param Compilable $subQuery The sub query.
     *
     * @return $this This compiler instance.
     * @throws PrimeException
     */
    protected function addQueryBindings(CompilableClause $clause, Compilable $subQuery)
    {
        foreach ($subQuery->getBindings() as $binding) {
            $this->bindRaw($clause, $binding); // Types are already converted on compilation
        }

        return $this;
    }

    /**
     * Creates a new positional parameter and bind the given value to it.
     * The value will be converted according to the given type. If the type is not defined, it will be resolved from PHP value.
     *
     * Attention: If you are using positional parameters with the query builder you have
     * to be very careful to bind all parameters in the order they appear in the SQL
     * statement , otherwise they get bound in the wrong order which can lead to serious
     * bugs in your code.
     *
     * @param SqlQueryInterface&CompilableClause $query
     * @param mixed $value
     * @param TypeInterface|null $type The type to bind, or null to resolve
     *
     * @return string
     * @throws PrimeException
     */
    protected function bindTyped(CompilableClause $query, $value, ?TypeInterface $type)
    {
        return $this->bindRaw($query, $this->platform()->types()->toDatabase($value, $type));
    }

    /**
     * Creates a new positional parameter and bind the given value to it.
     * The value will not be converted here
     *
     * Attention: If you are using positional parameters with the query builder you have
     * to be very careful to bind all parameters in the order they appear in the SQL
     * statement , otherwise they get bound in the wrong order which can lead to serious
     * bugs in your code.
     *
     * @param SqlQueryInterface&CompilableClause $query
     * @param mixed $value Raw database value : must be converted before
     *
     * @return string
     */
    protected function bindRaw(CompilableClause $query, $value)
    {
        $query->state()->bind($value);

        return '?';
    }

    /**
     * @param SqlQueryInterface&CompilableClause $query
     *
     * @return array
     */
    public function getBindings(CompilableClause $query)
    {
        return $this->mergeBindings($query->state()->bindings);
    }

    /**
     * Merge algo for bindings and binding types
     * 
     * @param array $bindings
     *
     * @return array
     */
    protected function mergeBindings($bindings)
    {
        $mergedBindings = [];

        if (isset($bindings[0])) {
            $mergedBindings = $bindings[0];
        } else {
            foreach (['columns', 'joins', 'where', 'groups', 'having', 'orders'] as $part) {
                if (isset($bindings[$part])) {
                    $mergedBindings = array_merge($mergedBindings, $bindings[$part]);
                }
            }
        }

        return $mergedBindings;
    }
}
