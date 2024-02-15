<?php

namespace Bdf\Prime\Query\Factory;

use Bdf\Prime\Connection\ConnectionInterface;
use Bdf\Prime\Query\CommandInterface;
use Bdf\Prime\Query\Compiled\CompiledQueryInterface;
use Bdf\Prime\Query\Compiler\CompilerInterface;
use Bdf\Prime\Query\Compiler\Preprocessor\PreprocessorInterface;
use LogicException;

/**
 * Base query factory
 * This factory can register query aliases
 */
class DefaultQueryFactory implements QueryFactoryInterface
{
    /**
     * @var ConnectionInterface
     */
    private $connection;

    /**
     * @var object
     */
    private $defaultCompiler;

    /**
     * Map query class name to compiler instance or class name
     *
     * @var array<class-string<CommandInterface>, class-string|object>
     */
    private $compilers = [];

    /**
     * Map query name to query class name
     *
     * @var class-string-map<Q as CommandInterface, class-string<Q>>
     */
    private $alias = [];

    /**
     * @var class-string<CompiledQueryInterface>|null
     */
    private ?string $compiledQueryClass;

    /**
     * DefaultQueryFactory constructor.
     *
     * @param ConnectionInterface $connection
     * @param object $defaultCompiler
     * @param array<class-string<CommandInterface>, class-string> $compilers
     * @param array<class-string<CommandInterface>, class-string<CommandInterface>> $alias
     * @param class-string<CompiledQueryInterface>|null $compiledQueryClass
     */
    public function __construct(ConnectionInterface $connection, object $defaultCompiler, array $compilers, array $alias, ?string $compiledQueryClass = null)
    {
        $this->connection = $connection;
        $this->defaultCompiler = $defaultCompiler;
        $this->compilers = $compilers;
        $this->alias = $alias;
        $this->compiledQueryClass = $compiledQueryClass;
    }

    /**
     * Register a custom compiler for a query
     *
     * @param class-string<CommandInterface> $query The query class name
     * @param class-string|object $compiler The query compiler, or its class name
     *
     * @return void
     */
    public function register(string $query, $compiler): void
    {
        $this->compilers[$query] = $compiler;
    }

    /**
     * Register a query alias
     *
     * @param class-string<Q> $alias The query alias
     * @param class-string<Q> $query The query class name
     *
     * @return void
     *
     * @template C as ConnectionInterface
     * @template Q as CommandInterface<C>
     */
    public function alias(string $alias, string $query)
    {
        $this->alias[$alias] = $query;
    }

    /**
     * {@inheritdoc}
     */
    public function make(string $name, PreprocessorInterface $preprocessor = null): CommandInterface
    {
        $query = $this->alias[$name] ?? $name;

        return new $query($this->connection, $preprocessor);
    }

    /**
     * {@inheritdoc}
     */
    public function compiler(string $query): object
    {
        if (!isset($this->compilers[$query])) {
            return $this->defaultCompiler;
        }

        $compiler = $this->compilers[$query];

        if (is_string($compiler)) {
            return $this->compilers[$query] = new $compiler($this->connection);
        }

        return $compiler;
    }

    /**
     * {@inheritdoc}
     */
    public function compiled($query): CompiledQueryInterface
    {
        if (!$this->compiledQueryClass) {
            throw new LogicException('This connection does not support compiled queries');
        }

        return new ($this->compiledQueryClass)($this->connection, $query);
    }
}
