<?php

namespace Bdf\Prime\Query;

use Bdf\Prime\Connection\ConnectionInterface;
use Bdf\Prime\Connection\Result\ResultSetInterface;
use Bdf\Prime\Exception\PrimeException;
use Bdf\Prime\Query\Compiler\CompilerInterface;
use Bdf\Prime\Query\Compiler\Preprocessor\DefaultPreprocessor;
use Bdf\Prime\Query\Compiler\Preprocessor\PreprocessorInterface;
use Bdf\Prime\Query\Compiler\QuoteCompilerInterface;
use Bdf\Prime\Query\Compiler\SqlCompiler;
use Bdf\Prime\Query\Contract\Compilable;
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
use Stringable;

/**
 * Sql Query
 *
 * @package Bdf\Prime\Query
 *
 * @todo comment reset un statement (ex ecraser les orders). Prendre en compte le reset du compiler
 *
 * @template C as \Bdf\Prime\Connection\ConnectionInterface&\Doctrine\DBAL\Connection
 * @template R as object|array
 *
 * @extends AbstractQuery<C, R>
 * @implements SqlQueryInterface<C, R>
 * @implements Paginable<R>
 *
 * @property array{
 *     ignore: bool|null,
 *     replace: bool|null,
 *     values: array,
 *     columns: array,
 *     distinct: bool|null,
 *     tables: array,
 *     joins: array,
 *     where: array,
 *     groups: list<string>,
 *     having: array,
 *     orders: array,
 *     limit: int|null,
 *     offset: int|null,
 *     aggregate: array|null,
 *     lock: int|null,
 *     ...
 * } $statements
 */
class Query extends AbstractQuery implements SqlQueryInterface, Paginable, Stringable
{
    use EntityJoinTrait;
    /** @use PaginableTrait<R> */
    use PaginableTrait;
    use LimitableTrait;
    use OrderableTrait;
    use SimpleJoinTrait;
    use LockableTrait;

    /**
     * Initializes a new <tt>Query</tt>.
     *
     * @param C $connection The DBAL Connection.
     * @param PreprocessorInterface|null $preprocessor
     */
    public function __construct(ConnectionInterface $connection, ?PreprocessorInterface $preprocessor = null)
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
     *
     * @return CompilerInterface<Query>&QuoteCompilerInterface
     */
    public function compiler(): object
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
    public function quote($value, ?int $type = null): string
    {
        return $this->connection->quote($value, $type);
    }

    /**
     * {@inheritdoc}
     */
    public function quoteIdentifier(string $column): string
    {
        return $this->compiler()->quoteIdentifier($this, $column);
    }

    /**
     * {@inheritdoc}
     */
    #[WriteOperation]
    public function delete(): int
    {
        return $this->executeUpdate(self::TYPE_DELETE);
    }

    /**
     * {@inheritdoc}
     */
    #[WriteOperation]
    public function update(array $data = [], array $types = []): int
    {
        if ($data) {
            $this->statements['values'] = [
                'data' => $data,
                'types' => $types,
            ];
        }

        return $this->executeUpdate(self::TYPE_UPDATE);
    }

    /**
     * {@inheritdoc}
     */
    #[WriteOperation]
    public function insert(array $data = []): int
    {
        if ($data) {
            $this->statements['values'] = [
                'data' => $data,
            ];
        }

        return $this->executeUpdate(self::TYPE_INSERT);
    }

    /**
     * {@inheritdoc}
     */
    public function ignore(bool $flag = true)
    {
        $this->statements['ignore'] = $flag;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    #[WriteOperation]
    public function replace(array $values = []): int
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
     * @param Compilable::TYPE_* $type The query type
     *
     * @return int The number of updated rows
     *
     * @throws PrimeException
     */
    protected function executeUpdate(string $type): int
    {
        $this->setType($type);

        $result = $this->connection->execute($this);

        if ($result->hasWrite()) {
            $this->clearCacheOnWrite();
        }

        return $result->count();
    }

    /**
     * {@inheritdoc}
     */
    #[ReadOperation]
    public function execute($columns = null): ResultSetInterface
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
    protected function cacheKey(): ?string
    {
        return sha1($this->toSql().'-'.serialize($this->getBindings()));
    }

    /**
     * {@inheritdoc}
     */
    #[ReadOperation]
    public function paginationCount(?string $column = null): int
    {
        $statements = $this->statements;

        $this->compilerState->invalidate(['columns', 'orders']);

        $this->statements['orders'] = [];
        $this->statements['limit'] = null;
        $this->statements['offset'] = null;
        $this->statements['aggregate'] = ['pagination', $this->getPaginationColumns($column)];

        $count = (int)$this->execute()->current()['aggregate'];

        $this->compilerState->invalidate(['columns', 'orders']);
        $this->statements = $statements;

        return $count;
    }

    /**
     * Get the column to count for pagination
     * @todo Voir pour count sur PK quand une entité est liée ?
     * @todo array ?
     *
     * @param string|null $column
     *
     * @return string
     */
    protected function getPaginationColumns(?string $column): string
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
    public function count(?string $column = null): int
    {
        return (int)$this->aggregate(__FUNCTION__, $column);
    }

    /**
     * {@inheritdoc}
     */
    #[ReadOperation]
    public function avg(?string $column = null): float
    {
        return (float)$this->aggregate(__FUNCTION__, $column);
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
        return (float)$this->aggregate(__FUNCTION__, $column);
    }

    /**
     * {@inheritdoc}
     */
    #[ReadOperation]
    public function aggregate(string $function, ?string $column = null)
    {
        $statements = $this->statements;

        $this->compilerState->invalidate('columns');

        $this->statements['aggregate'] = [$function, $column ?: '*'];

        $aggregate = $this->execute()->current()['aggregate'];

        $this->compilerState->invalidate('columns');
        $this->statements = $statements;

        return $aggregate;
    }

    /**
     * {@inheritdoc}
     */
    public function distinct(bool $flag = true)
    {
        $this->compilerState->invalidate('columns');

        $this->statements['distinct'] = $flag;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param string|Query $from The table name, or the embedded query
     */
    public function from($from, ?string $alias = null)
    {
        $this->compilerState->invalidate('from');

        $table = [
            'table' => $from,
            'alias' => $alias,
        ];
        $key = $alias ?: $from;

        if (is_string($key)) {
            $this->statements['tables'][$key] = $table;
        } else {
            $this->statements['tables'][] = $table;
        }

        return $this;
    }

    /**
     * Change a FROM alias for a table (or previously defined alias)
     *
     * Usage:
     * <code>
     * // Change alias of the current table : FROM my_table as my_alias
     * $query->from('my_table')->fromAlias('my_alias');
     *
     * // Change alias of the foo table : FROM foo as my_alias, bar
     * $query->from('foo')->from('bar')->fromAlias('my_alias', 'foo');
     *
     * // Redefine alias of foo : FROM foo as my_alias, bar
     * $query->from('foo', 'f')->from('bar')->fromAlias('my_alias', 'f');
     * </code>
     *
     * @param string $alias The new alias name
     * @param string|null $table The last alias / table name. If null, will define the last table
     *
     * @return $this
     */
    public function fromAlias(string $alias, ?string $table = null)
    {
        $this->compilerState->invalidate('from');

        $table = $table ?? key($this->statements['tables']);

        $this->statements['tables'][$alias] = $this->statements['tables'][$table];
        $this->statements['tables'][$alias]['alias'] = $alias;

        unset($this->statements['tables'][$table]);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function group(string ...$columns)
    {
        $this->compilerState->invalidate('groups');

        $this->statements['groups'] = $columns;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @no-named-arguments
     */
    public function addGroup(string ...$columns)
    {
        $this->compilerState->invalidate('groups');

        $this->statements['groups'] = [...$this->statements['groups'], ...$columns];

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
    public function havingNull(string $column, string $type = CompositeExpression::TYPE_AND)
    {
        $this->compilerState->invalidate('having');

        return $this->buildClause('having', $column, '=', null, $type);
    }

    /**
     * {@inheritdoc}
     */
    public function havingNotNull(string $column, string $type = CompositeExpression::TYPE_AND)
    {
        $this->compilerState->invalidate('having');

        return $this->buildClause('having', $column, '!=', null, $type);
    }

    /**
     * {@inheritdoc}
     */
    public function orHavingNull(string $column)
    {
        return $this->havingNull($column, CompositeExpression::TYPE_OR);
    }

    /**
     * {@inheritdoc}
     */
    public function orHavingNotNull(string $column)
    {
        return $this->havingNotNull($column, CompositeExpression::TYPE_OR);
    }

    /**
     * {@inheritdoc}
     */
    public function havingRaw($raw, string $type = CompositeExpression::TYPE_AND)
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
    public function addCommand(string $command, $value)
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
    public function toSql(): string
    {
        return $this->compile();
    }

    /**
     * {@inheritdoc}
     *
     * @todo A reprendre: utiliser les types des bindings
     *
     * @return string
     */
    public function toRawSql(): string
    {
        $keys   = [];
        $sql    = $this->toSql();
        $values = $this->compiler()->getBindings($this);

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
    public function __toString(): string
    {
        return $this->toSql();
    }
}
