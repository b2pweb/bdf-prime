<?php

namespace Bdf\Prime\Query\Custom\KeyValue;

use Bdf\Prime\Connection\ConnectionInterface;
use Bdf\Prime\Exception\DBALException;
use Bdf\Prime\Exception\PrimeException;
use Bdf\Prime\Query\AbstractReadCommand;
use Bdf\Prime\Query\Compiler\Preprocessor\DefaultPreprocessor;
use Bdf\Prime\Query\Compiler\Preprocessor\PreprocessorInterface;
use Bdf\Prime\Query\Contract\Compilable;
use Bdf\Prime\Query\Contract\Limitable;
use Bdf\Prime\Query\Contract\Paginable;
use Bdf\Prime\Query\Contract\Query\KeyValueQueryInterface;
use Bdf\Prime\Query\Contract\ReadOperation;
use Bdf\Prime\Query\Contract\WriteOperation;
use Bdf\Prime\Query\Extension\CompilableTrait;
use Bdf\Prime\Query\Extension\LimitableTrait;
use Bdf\Prime\Query\Extension\PaginableTrait;
use Bdf\Prime\Query\Extension\ProjectionableTrait;
use Doctrine\DBAL\DBALException as BaseDBALException;

/**
 * Query for perform simple key / value search
 *
 * This query can only perform "equals" comparison, with "AND" combination only on the current table (no relation resolve)
 *
 * <code>
 * $query
 *     ->from('test_')
 *     ->where('foo', 'bar')
 *     ->first()
 * ;
 * </code>
 */
class KeyValueQuery extends AbstractReadCommand implements KeyValueQueryInterface, Compilable, Paginable, Limitable
{
    use CompilableTrait;
    use LimitableTrait;
    use PaginableTrait;
    use ProjectionableTrait;


    /**
     * KeyValueQuery constructor.
     *
     * @param ConnectionInterface $connection
     * @param PreprocessorInterface|null $preprocessor
     */
    public function __construct(ConnectionInterface $connection, PreprocessorInterface $preprocessor = null)
    {
        parent::__construct($connection, $preprocessor ?: new DefaultPreprocessor());

        $this->statements = [
            'where'     => [],
            'table'     => null,
            'columns'   => [],
            'aggregate' => null,
            'limit'     => null,
            'offset'    => null,
            'values'    => [
                'data'  => [],
                'types' => []
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function from($table, $alias = null)
    {
        if ($this->statements['table'] !== $table) {
            $this->compilerState->invalidate('columns');
            $this->statements['table'] = $table;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function where($field, $value = null)
    {
        if (is_array($field)) {
            if (array_keys($field) !== array_keys($this->statements['where'])) {
                $this->compilerState->invalidate('where');
            }

            $this->statements['where'] = $field + $this->statements['where'];
        } else {
            if (!isset($this->statements['where'][$field])) {
                $this->compilerState->invalidate('where');
            }

            $this->statements['where'][$field] = $value;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function values(array $values = [], array $types = [])
    {
        if (array_keys($values) !== array_keys($this->statements['values']) || $types !== $this->statements['values']['types']) {
            $this->compilerState->invalidate('values');
        }

        $this->statements['values'] = [
            'data'  => $values,
            'types' => $types,
        ];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    #[ReadOperation]
    public function count($column = null)
    {
        return (int) $this->aggregate(__FUNCTION__, $column);
    }

    /**
     * {@inheritdoc}
     */
    #[ReadOperation]
    public function avg($column = null)
    {
        return (float) $this->aggregate(__FUNCTION__, $column);
    }

    /**
     * {@inheritdoc}
     */
    #[ReadOperation]
    public function min($column = null)
    {
        return $this->aggregate(__FUNCTION__, $column);
    }

    /**
     * {@inheritdoc}
     */
    #[ReadOperation]
    public function max($column = null)
    {
        return $this->aggregate(__FUNCTION__, $column);
    }

    /**
     * {@inheritdoc}
     */
    #[ReadOperation]
    public function sum($column = null)
    {
        return (float) $this->aggregate(__FUNCTION__, $column);
    }

    /**
     * {@inheritdoc}
     */
    #[ReadOperation]
    public function aggregate($function, $column = null)
    {
        $statements = $this->statements;

        $this->compilerState->invalidate();

        $this->statements['aggregate'] = [$function, $column ?: '*'];

        $aggregate = $this->execute()[0]['aggregate'];

        $this->compilerState->invalidate();
        $this->statements = $statements;

        return $aggregate;
    }

    /**
     * {@inheritdoc}
     */
    #[ReadOperation]
    public function paginationCount($columns = null)
    {
        $statements = $this->statements;

        $this->compilerState->invalidate();

        $this->statements['limit'] = null;
        $this->statements['offset'] = null;
        $this->statements['aggregate'] = ['count', $columns ?: '*'];

        $count = (int)$this->execute()[0]['aggregate'];

        $this->compilerState->invalidate();
        $this->statements = $statements;

        return $count;
    }

    /**
     * {@inheritdoc}
     */
    #[ReadOperation]
    public function execute($columns = null)
    {
        $this->setType(self::TYPE_SELECT);

        if (!empty($columns)) {
            $this->select($columns);
        }

        return $this->executeCached();
    }

    /**
     * {@inheritdoc}
     */
    #[WriteOperation]
    public function delete()
    {
        $this->setType(self::TYPE_DELETE);

        $count = $this->connection->execute($this)->count();

        if ($count > 0) {
            $this->clearCacheOnWrite();
        }

        return $count;
    }

    /**
     * {@inheritdoc}
     */
    #[WriteOperation]
    public function update($values = null)
    {
        if ($values !== null) {
            $this->values($values);
        }

        $this->setType(self::TYPE_UPDATE);

        $count = $this->connection->execute($this)->count();

        if ($count > 0) {
            $this->clearCacheOnWrite();
        }

        return $count;
    }

    /**
     * {@inheritdoc}
     */
    public function limit($limit, $offset = null)
    {
        if ($this->statements['limit'] === $limit && $this->statements['offset'] === $offset) {
            return $this;
        }

        // Do not reset query when changing pagination
        if ($offset !== null && $this->hasPagination()) {
            $this->statements['limit'] = $limit;
            $this->statements['offset'] = $offset;

            return $this;
        }

        $this->compilerState->invalidate();

        $this->statements['limit'] = $limit;

        if ($offset !== null) {
            $this->statements['offset'] = $offset;
        }

        return $this;
    }

    /**
     * Get the SQL query
     *
     * @return string
     * @throws PrimeException
     */
    public function toSql()
    {
        $this->compile();

        return $this->compilerState->compiledParts['sql'] ?? false;
    }

    /**
     * {@inheritdoc}
     */
    public function getBindings()
    {
        return $this->compiler->getBindings($this);
    }

    /**
     * Get cache namespace
     *
     * @return string
     */
    protected function cacheNamespace()
    {
        return $this->connection->getName().':'.$this->statements['table'];
    }

    /**
     * Get the cache key
     *
     * @return string
     */
    protected function cacheKey()
    {
        $sql = $this->toSql();

        if (!$sql) {
            return null;
        }

        return sha1($sql.'-'.serialize($this->getBindings()));
    }
}
