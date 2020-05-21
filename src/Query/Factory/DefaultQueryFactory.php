<?php

namespace Bdf\Prime\Query\Factory;

use Bdf\Prime\Connection\ConnectionInterface;
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
     * @var array
     */
    private $compilers = [];

    /**
     * Map query name to query class name
     *
     * @var string[]
     */
    private $alias = [];


    /**
     * DefaultQueryFactory constructor.
     *
     * @param ConnectionInterface $connection
     * @param CompilerInterface $defaultCompiler
     * @param array $compilers
     * @param string[] $alias
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
     * @param string $query The query class name
     * @param string|CompilerInterface $compiler The query compiler, or its class name
     *
     * @return void
     */
    public function register($query, $compiler)
    {
        $this->compilers[$query] = $compiler;
    }

    /**
     * Register a query alias
     *
     * @param string $alias The query alias
     * @param string $query The query class name
     *
     * @return void
     */
    public function alias($alias, $query)
    {
        $this->alias[$alias] = $query;
    }

    /**
     * {@inheritdoc}
     */
    public function make($name, PreprocessorInterface $preprocessor = null)
    {
        $query = $this->alias[$name] ?? $name;

        return new $query($this->connection, $preprocessor);
    }

    /**
     * {@inheritdoc}
     */
    public function compiler($query)
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
