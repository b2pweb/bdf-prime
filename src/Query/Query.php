<?php

namespace Bdf\Prime\Query;

use Bdf\Prime\Connection\ConnectionInterface;
use Bdf\Prime\Exception\PrimeException;
use Bdf\Prime\Query\Compiler\Preprocessor\DefaultPreprocessor;
use Bdf\Prime\Query\Compiler\Preprocessor\PreprocessorInterface;
use Bdf\Prime\Query\Contract\Paginable;
use Bdf\Prime\Query\Contract\ReadOperation;
use Bdf\Prime\Query\Contract\WriteOperation;
use Bdf\Prime\Query\Expression\Raw;
use Bdf\Prime\Query\Extension\EntityJoinTrait;
use Bdf\Prime\Query\Extension\LimitableTrait;
use Bdf\Prime\Query\Extension\LockableTrait;
use Bdf\Prime\Query\Extension\OrderableTrait;
use Bdf\Prime\Query\Extension\PaginableTrait;
use Bdf\Prime\Query\Extension\SimpleJoinTrait;
use Doctrine\DBAL\Query\Expression\CompositeExpression;

/**
 * Sql Query
 * 
 * @package Bdf\Prime\Query
 * 
 * @todo comment reset un statement (ex ecraser les orders). Prendre en compte le reset du compiler
 */
class Query extends AbstractQuery implements SqlQueryInterface, Paginable
{
    use EntityJoinTrait;
    use PaginableTrait;
    use LimitableTrait;
    use OrderableTrait;
    use SimpleJoinTrait;
    use LockableTrait;

    /**
     * Initializes a new <tt>Query</tt>.
     *
     * @param ConnectionInterface $connection The DBAL Connection.
     * @param PreprocessorInterface|null $preprocessor
     */
    public function __construct(ConnectionInterface $connection, PreprocessorInterface $preprocessor = null)
    {
        parent::__construct($connection, $preprocessor ?: new DefaultPreprocessor());

        $this->statements = [
            'ignore'     => null,
            'replace'    => null,
            'values'     => [],
            'columns'    => [],
            'distinct'   => null,
            'tables'     => [],
            'joins'      => [],
            'where'      => [],
            'groups'     => [],
            'having'     => [],
            'orders'     => [],
            'limit'      => null,
            'offset'     => null,
            'aggregate'  => null,
            'lock'       => null,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getBindings()
    {
        return $this->compiler->getBindings($this);
    }

    /**
     * {@inheritdoc}
     */
    public function raw($sql)
    {
        return new Raw($sql);
    }

    /**
     * {@inheritdoc}
     */
    public function quote($value, $type = null)
    {
        return $this->compiler->quote($value, $type);
    }

    /**
     * {@inheritdoc}
     *
     * @todo Utile ?
     */
    public function quoteIdentifier($column)
    {
        return $this->compiler->quoteIdentifier($this, $column);
    }

    /**
     * {@inheritdoc}
     */
    #[WriteOperation]
    public function delete()
    {
        return $this->executeUpdate(self::TYPE_DELETE);
    }

    /**
     * {@inheritdoc}
     */
    #[WriteOperation]
    public function update(array $data = [], array $types = [])
    {
        if ($data) {
            $this->statements['values'] = [
                'data'   => $data,
                'types'  => $types,
            ];
        }
        
        return $this->executeUpdate(self::TYPE_UPDATE);
    }

    /**
     * {@inheritdoc}
     */
    #[WriteOperation]
    public function insert(array $data = [])
    {
        if ($data) {
            $this->statements['values'] = [
                'data'   => $data,
            ];
        }
        
        return $this->executeUpdate(self::TYPE_INSERT);
    }

    /**
     * {@inheritdoc}
     */
    public function ignore($flag = true)
    {
        $this->statements['ignore'] = (bool)$flag;
        
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    #[WriteOperation]
    public function replace(array $values = [])
    {
        $this->statements['replace'] = true;

        return $this->insert($values);
    }

    /**
     * Set values to insert or update
     *
     * @todo Remonter sur une interface ?
     *
     * <code>
     * // Perform a INSERT INTO ... SELECT ... query
     * $query
     *     ->from('users_bck')
     *     ->values($connection->builder()->from('users'))
     *     ->insert()
     * ;
     *
     * // Perform a REPLACE INTO ... SELECT ... with column mapping
     * $query
     *     ->from('users_bck')
     *     ->values(
     *         $connection->builder()
     *             ->from('users')
     *             ->select([
     *                 'backup_name' => 'name', // Map "name" column from select table to "backup_name" column to insert table
     *                 'id'                     // Use "id" column without mapping other than ORM data mapping
     *             ])
     *     )
     *     ->replace() // Replace can also be used
     * ;
     *
     * // Simple UPDATE query
     * $query->values(['foo' => 'bar'])->update();
     * </code>
     *
     * @param QueryInterface|array $data The values, in form [column] => [value], or the SELECT query
     * @param array $types The binding types Do not works with INSERT INTO SELECT query
     *
     * @return $this
     */
    public function values($data = [], array $types = [])
    {
        $this->statements['values'] = [
            'data' => $data,
            'type' => $types
        ];

        return $this;
    }

    /**
     * Executes this query as an update query
     *
     * @param string $type The query type
     *
     * @return int The number of updated rows
     *
     * @throws PrimeException
     */
    protected function executeUpdate($type)
    {
        $this->setType($type);

        $nb = $this->connection->execute($this)->count();

        if ($nb > 0) {
            $this->clearCacheOnWrite();
        }

        return $nb;
    }

    /**
     * {@inheritdoc}
     *
     * @todo Return statement instead of array ?
     */
    #[ReadOperation]
    public function execute($columns = null)
    {
        if (!empty($columns)) {
            $this->select($columns);
        }

        $this->setType(self::TYPE_SELECT);

        return $this->executeCached();
    }

    /**
     * {@inheritdoc}
     */
    protected function cacheKey()
    {
        return sha1($this->toSql().'-'.serialize($this->getBindings()));
    }

    /**
     * {@inheritdoc}
     */
    #[ReadOperation]
    public function paginationCount($columns = null)
    {
        $statements = $this->statements;

        $this->compilerState->invalidate(['columns', 'orders']);

        $this->statements['orders'] = [];
        $this->statements['limit'] = null;
        $this->statements['offset'] = null;
        $this->statements['aggregate'] = ['pagination', $this->getPaginationColumns($columns)];

        $count = (int)$this->execute()[0]['aggregate'];

        $this->compilerState->invalidate(['columns', 'orders']);
        $this->statements = $statements;

        return $count;
    }

    /**
     * Get the column to count for pagination
     * @todo Voir pour count sur PK quand une entité est liée ?
     *
     * @param array|string|null $column
     *
     * @return string
     */
    protected function getPaginationColumns($column)
    {
        if (!empty($column)) {
            return $column;
        }

        // If distinct is on and no column are given, we use the current column
        if ($this->statements['distinct'] && !empty($this->statements['columns'])) {
            return $this->statements['columns'][0]['column'];
        }

        // If group by we use the columns of the group by
        if ($this->statements['groups']) {
            $this->statements['distinct'] = true;
            $column = $this->statements['groups'][0];
            $this->statements['groups'] = [];

            return $column;
        }

        return '*';
    }

    /**
     * {@inheritdoc}
     */
    #[ReadOperation]
    public function count($column = null)
    {
        return (int)$this->aggregate(__FUNCTION__, $column);
    }

    /**
     * {@inheritdoc}
     */
    #[ReadOperation]
    public function avg($column = null)
    {
        return (float)$this->aggregate(__FUNCTION__, $column);
    }

    /**
     * {@inheritdoc}
     */
    #[ReadOperation]
    public function min($column = null)
    {
        return (float)$this->aggregate(__FUNCTION__, $column);
    }

    /**
     * {@inheritdoc}
     */
    #[ReadOperation]
    public function max($column = null)
    {
        return (float)$this->aggregate(__FUNCTION__, $column);
    }

    /**
     * {@inheritdoc}
     */
    #[ReadOperation]
    public function sum($column = null)
    {
        return (float)$this->aggregate(__FUNCTION__, $column);
    }

    /**
     * {@inheritdoc}
     */
    #[ReadOperation]
    public function aggregate($function, $column = null)
    {
        $statements = $this->statements;

        $this->compilerState->invalidate('columns');

        $this->statements['aggregate'] = [$function, $column ?: '*'];

        $aggregate = $this->execute()[0]['aggregate'];

        $this->compilerState->invalidate('columns');
        $this->statements = $statements;

        return $aggregate;
    }

    /**
     * {@inheritdoc}
     */
    public function distinct($flag = true)
    {
        $this->compilerState->invalidate('columns');
        
        $this->statements['distinct'] = (bool)$flag;
        
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function from($from, $alias = null)
    {
        $this->compilerState->invalidate('from');
        
        $this->statements['tables'][] = [
            'table' => $from,
            'alias' => $alias,
        ];
        
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function group($column)
    {
        $this->compilerState->invalidate('groups');
        
        $this->statements['groups'] = is_array($column) ? $column : func_get_args();
        
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addGroup($column)
    {
        $this->compilerState->invalidate('groups');
        
        $this->statements['groups'] = array_merge($this->statements['groups'], is_array($column) ? $column : func_get_args());
        
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function having($column, $operator = null, $value = null)
    {
        $this->compilerState->invalidate('having');

        return $this->buildClause('having', $column, $operator, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function orHaving($column, $operator = null, $value = null)
    {
        $this->compilerState->invalidate('having');

        return $this->buildClause('having', $column, $operator, $value, CompositeExpression::TYPE_OR);
    }

    /**
     * {@inheritdoc}
     */
    public function havingNull($column, $type = CompositeExpression::TYPE_AND)
    {
        $this->compilerState->invalidate('having');

        return $this->buildClause('having', $column, '=', null, $type);
    }

    /**
     * {@inheritdoc}
     */
    public function havingNotNull($column, $type = CompositeExpression::TYPE_AND)
    {
        $this->compilerState->invalidate('having');

        return $this->buildClause('having', $column, '!=', null, $type);
    }

    /**
     * {@inheritdoc}
     */
    public function orHavingNull($column)
    {
        return $this->havingNull($column, CompositeExpression::TYPE_OR);
    }

    /**
     * {@inheritdoc}
     */
    public function orHavingNotNull($column)
    {
        return $this->havingNotNull($column, CompositeExpression::TYPE_OR);
    }

    /**
     * {@inheritdoc}
     */
    public function havingRaw($raw, $type = CompositeExpression::TYPE_AND)
    {
        $this->compilerState->invalidate('having');

        return $this->buildRaw('having', $raw, $type);
    }

    /**
     * {@inheritdoc}
     */
    public function orHavingRaw($raw)
    {
        return $this->havingRaw($raw, CompositeExpression::TYPE_OR);
    }

    /**
     * {@inheritdoc}
     */
    public function addCommand($command, $value)
    {
        switch ($command) {
            case ':limit':
                if (is_array($value)) {
                    $this->limit($value[0], $value[1]);
                } else {
                    $this->limit($value);
                }
                break;
            
            case ':limitPage':
                if (is_array($value)) {
                    $this->limitPage($value[0], $value[1]);
                } else {
                    $this->limitPage($value);
                }
                break;
            
            case ':offset':
                $this->offset($value);
                break;
            
            case ':order':
                $this->order($value);
                break;
            
            case ':distinct':
                $this->distinct($value);
                break;
                
            case ':group':
                $this->group($value);
                break;
            
            case ':having':
                $this->having($value);
                break;
        }
        
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function toSql()
    {
        return $this->compile();
    }

    /**
     * {@inheritdoc}
     * @todo A reprendre: utiliser les types des bindings
     */
    public function toRawSql()
    {
        $keys   = [];
        $sql    = $this->toSql();
        $values = $this->compiler->getBindings($this);

        # build a regular expression for each parameter
        foreach ($values as $key => $value) {
            if (is_string($key)) {
                $keys[] = '/:' . $key . '/';
            } else {
                $keys[] = '/[?]/';
            }
            
            if (is_array($value)) {
                $values[$key] = implode(',', $this->connection->quote($value));
            } elseif (is_null($value)) {
                $values[$key] = 'NULL';
            } elseif ($value instanceof \DateTimeInterface) {
                $values[$key] = $value->format($this->connection->platform()->grammar()->getDateTimeFormatString());
            } elseif (is_string($value)) {
                $values[$key] = $this->connection->quote($value);
            } else {
                $values[$key] = $value;
            }
        }
        
        return preg_replace($keys, $values, $sql, 1);
    }
    
    /**
     * Gets a string representation of this Query which corresponds to
     * the final SQL query being constructed.
     *
     * @return string The string representation of this Query.
     */
    public function __toString()
    {
        return $this->toSql();
    }
}
