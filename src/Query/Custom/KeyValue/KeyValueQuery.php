<?php

namespace Bdf\Prime\Query\Custom\KeyValue;

use Bdf\Prime\Connection\ConnectionInterface;
use Bdf\Prime\Connection\Result\ResultSetInterface;
use Bdf\Prime\Exception\PrimeException;
use Bdf\Prime\Query\AbstractReadCommand;
use Bdf\Prime\Query\Compiler\CompilerInterface;
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
 *
 * @template C as \Bdf\Prime\Connection\ConnectionInterface
 * @template R as object|array
 *
 * @implements KeyValueQueryInterface<C, R>
 * @implements Paginable<R>
 * @extends AbstractReadCommand<C, R>
 */
class KeyValueQuery extends AbstractReadCommand implements KeyValueQueryInterface, Compilable, Paginable, Limitable
{
    use CompilableTrait;
    use LimitableTrait;
    /** @use PaginableTrait<R> */
    use PaginableTrait;
    use ProjectionableTrait;


    /**
     * KeyValueQuery constructor.
     *
     * @param C $connection
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
    public function from(string $from, ?string $alias = null)
    {
        if ($this->statements['table'] !== $from) {
            $this->compilerState->invalidate('columns');
            $this->statements['table'] = $from;
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
    public function count(?string $column = null): int
    {
        return (int) $this->aggregate(__FUNCTION__, $column);
    }

    /**
     * {@inheritdoc}
     */
    #[ReadOperation]
    public function avg(?string $column = null): float
    {
        return (float) $this->aggregate(__FUNCTION__, $column);
    }

    /**
     * {@inheritdoc}
     */
    #[ReadOperation]
    public function min(?string $column = null)
    {
        return $this->aggregate(__FUNCTION__, $column);
    }

    /**
     * {@inheritdoc}
     */
    #[ReadOperation]
    public function max(?string $column = null)
    {
        return $this->aggregate(__FUNCTION__, $column);
    }

    /**
     * {@inheritdoc}
     */
    #[ReadOperation]
    public function sum(?string $column = null): float
    {
        return (float) $this->aggregate(__FUNCTION__, $column);
    }

    /**
     * {@inheritdoc}
     */
    #[ReadOperation]
    public function aggregate(string $function, ?string $column = null)
    {
        $statements = $this->statements;

        $this->compilerState->invalidate();

        $this->statements['aggregate'] = [$function, $column ?: '*'];

        $aggregate = $this->execute()->current()['aggregate'];

        $this->compilerState->invalidate();
        $this->statements = $statements;

        return $aggregate;
    }

    /**
     * {@inheritdoc}
     */
    #[ReadOperation]
    public function paginationCount(?string $column = null): int
    {
        $statements = $this->statements;

        $this->compilerState->invalidate();

        $this->statements['limit'] = null;
        $this->statements['offset'] = null;
        $this->statements['aggregate'] = ['count', $column ?: '*'];

        $count = (int)$this->execute()->current()['aggregate'];

        $this->compilerState->invalidate();
        $this->statements = $statements;

        return $count;
    }

    /**
     * {@inheritdoc}
     */
    #[ReadOperation]
    public function execute($columns = null): ResultSetInterface
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
    public function delete(): int
    {
        return $this->executeWrite(self::TYPE_DELETE);
    }

    /**
     * {@inheritdoc}
     */
    #[WriteOperation]
    public function update($values = null): int
    {
        if ($values !== null) {
            $this->values($values);
        }

        return $this->executeWrite(self::TYPE_UPDATE);
    }

    /**
     * {@inheritdoc}
     */
    public function limit(?int $limit, ?int $offset = null)
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
     * @return string|false
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
    public function compiler(): KeyValueSqlCompiler
    {
        return parent::compiler();
    }

    /**
     * {@inheritdoc}
     */
    public function getBindings(): array
    {
        return $this->compiler()->getBindings($this);
    }

    /**
     * {@inheritdoc}
     */
    protected function cacheNamespace(): string
    {
        return $this->connection->getName().':'.$this->statements['table'];
    }

    /**
     * {@inheritdoc}
     */
    protected function cacheKey(): ?string
    {
        $sql = $this->toSql();

        if (!$sql) {
            return null;
        }

        return sha1($sql.'-'.serialize($this->getBindings()));
    }

    /**
     * @param Compilable::TYPE_* $type
     * @return int
     */
    private function executeWrite(string $type): int
    {
        $this->setType($type);

        $result = $this->connection->execute($this);

        if ($result->hasWrite()) {
            $this->clearCacheOnWrite();
        }

        return $result->count();
    }
}
