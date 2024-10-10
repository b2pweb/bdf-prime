<?php

namespace Bdf\Prime\Query\Custom\BulkInsert;

use Bdf\Prime\Connection\ConnectionInterface;
use Bdf\Prime\Connection\Result\ResultSetInterface;
use Bdf\Prime\Query\CompilableClause;
use Bdf\Prime\Query\Compiler\CompilerInterface;
use Bdf\Prime\Query\Compiler\CompilerState;
use Bdf\Prime\Query\Compiler\Preprocessor\DefaultPreprocessor;
use Bdf\Prime\Query\Compiler\Preprocessor\PreprocessorInterface;
use Bdf\Prime\Query\Compiler\QuoteCompilerInterface;
use Bdf\Prime\Query\Contract\Cachable;
use Bdf\Prime\Query\Contract\Compilable;
use Bdf\Prime\Query\Contract\Query\InsertQueryInterface;
use Bdf\Prime\Query\Contract\WriteOperation;
use Bdf\Prime\Query\Extension\CachableTrait;

/**
 * Handle optimised insert query
 *
 * <code>
 * // Simple insert
 * $insert
 *     ->into('perforn')
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
 *
 * @template C as \Bdf\Prime\Connection\ConnectionInterface&\Doctrine\DBAL\Connection
 * @implements InsertQueryInterface<C>
 */
class BulkInsertQuery extends CompilableClause implements Compilable, Cachable, InsertQueryInterface
{
    use CachableTrait;

    /**
     * The DBAL Connection.
     *
     * @var C
     */
    protected $connection;

    /**
     * The SQL compiler
     *
     * @var CompilerInterface<BulkInsertQuery>&QuoteCompilerInterface
     */
    protected $compiler;


    /**
     * BulkInsertQuery constructor.
     *
     * @param C $connection
     * @param PreprocessorInterface|null $preprocessor
     */
    public function __construct(ConnectionInterface $connection, ?PreprocessorInterface $preprocessor = null)
    {
        parent::__construct($preprocessor ?: new DefaultPreprocessor(), new CompilerState());

        $this->on($connection);

        $this->statements = [
            'table'   => null,
            'columns' => [],
            'values'  => [],
            'mode'    => self::MODE_INSERT,
            'bulk'    => false
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @return CompilerInterface<BulkInsertQuery>&QuoteCompilerInterface
     */
    public function compiler(): object
    {
        return $this->compiler;
    }

    /**
     * {@inheritdoc}
     */
    public function on(ConnectionInterface $connection)
    {
        $this->connection = $connection;
        $this->compiler = $connection->factory()->compiler(static::class);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function connection(): ConnectionInterface
    {
        return $this->connection;
    }

    /**
     * {@inheritdoc}
     *
     * @return ResultSetInterface<array<string, mixed>>
     */
    #[WriteOperation]
    public function execute($columns = null): ResultSetInterface
    {
        $result = $this->connection->execute($this);

        if ($result->hasWrite()) {
            $this->clearCacheOnWrite();
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function into(string $table)
    {
        $this->compilerState->invalidate('table');
        $this->compilerState->invalidate('columns');

        // Reset columns when changing table
        $this->statements['columns'] = [];
        $this->statements['table'] = $table;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * Alias of into for compatibility purpose with interface
     *
     * @see BulkInsertQuery::into()
     */
    public function from(string $from, ?string $alias = null)
    {
        return $this->into($from);
    }

    /**
     * {@inheritdoc}
     */
    public function columns(array $columns)
    {
        $this->compilerState->invalidate('columns');

        $this->statements['columns'] = [];

        foreach ($columns as $name => $type) {
            if (is_int($name)) {
                $this->statements['columns'][] = [
                    'name' => $type,
                    'type' => null
                ];
            } else {
                $this->statements['columns'][] = [
                    'name' => $name,
                    'type' => $type
                ];
            }
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function values(array $data, bool $replace = false)
    {
        if (empty($this->statements['columns'])) {
            $this->columns(array_keys($data));
        }

        if ($this->statements['bulk']) {
            $this->compilerState->invalidate();
        }

        if (!$this->statements['bulk'] || $replace) {
            $this->statements['values'] = [$data];
        } else {
            $this->statements['values'][] = $data;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function mode(string $mode)
    {
        if ($mode !== $this->statements['mode']) {
            $this->compilerState->invalidate('mode');
            $this->statements['mode'] = $mode;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function ignore(bool $flag = true)
    {
        return $this->mode($flag ? self::MODE_IGNORE : self::MODE_INSERT);
    }

    /**
     * {@inheritdoc}
     */
    public function replace(bool $flag = true)
    {
        return $this->mode($flag ? self::MODE_REPLACE : self::MODE_INSERT);
    }

    /**
     * {@inheritdoc}
     */
    public function bulk(bool $flag = true)
    {
        $this->compilerState->invalidate();
        $this->statements['bulk'] = $flag;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function compile(bool $forceRecompile = false)
    {
        if (!$forceRecompile && $this->state()->compiled) {
            return $this->state()->compiled;
        }

        return $this->state()->compiled = $this->compiler->compileInsert($this);
    }

    /**
     * {@inheritdoc}
     */
    public function getBindings(): array
    {
        return $this->compiler->getBindings($this);
    }

    /**
     * {@inheritdoc}
     */
    public function type(): string
    {
        return self::TYPE_INSERT;
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
}
