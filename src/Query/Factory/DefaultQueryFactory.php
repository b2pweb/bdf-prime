<?php

namespace Bdf\Prime\Query\Factory;

use Bdf\Prime\Connection\ConnectionInterface;
use Bdf\Prime\Query\CommandInterface;
use Bdf\Prime\Query\Compiler\CompilerInterface;
use Bdf\Prime\Query\Compiler\Preprocessor\PreprocessorInterface;

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
     * @var CompilerInterface
     */
    private $defaultCompiler;

    /**
     * Map query class name to compiler instance or class name
     *
     * @var array<class-string<CommandInterface>, class-string<CompilerInterface>|CompilerInterface>
     */
    private $compilers = [];

    /**
     * Map query name to query class name
     *
     * @var class-string-map<Q as CommandInterface, class-string<Q>>
     */
    private $alias = [];


    /**
     * DefaultQueryFactory constructor.
     *
     * @param ConnectionInterface $connection
     * @param CompilerInterface $defaultCompiler
     * @param array<class-string<CommandInterface>, class-string<CompilerInterface>> $compilers
     * @param array<class-string<CommandInterface>, class-string<CommandInterface>> $alias
     */
    public function __construct(ConnectionInterface $connection, CompilerInterface $defaultCompiler, array $compilers, array $alias)
    {
        $this->connection = $connection;
        $this->defaultCompiler = $defaultCompiler;
        $this->compilers = $compilers;
        $this->alias = $alias;
    }

    /**
     * Register a custom compiler for a query
     *
     * @param class-string<CommandInterface> $query The query class name
     * @param class-string<CompilerInterface>|CompilerInterface $compiler The query compiler, or its class name
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
    public function compiler(string $query): CompilerInterface
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
}
