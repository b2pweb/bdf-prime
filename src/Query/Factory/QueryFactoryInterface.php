<?php

namespace Bdf\Prime\Query\Factory;

use Bdf\Prime\Query\CommandInterface;
use Bdf\Prime\Query\Compiled\CompiledQueryInterface;
use Bdf\Prime\Query\Compiler\Preprocessor\PreprocessorInterface;

/**
 * Factory type for create query objects
 *
 * @method CompiledQueryInterface compiled(mixed $query)
 */
interface QueryFactoryInterface
{
    /**
     * Make the query
     *
     * <code>
     * $factory->make(KeyValueQuery::class);
     * </code>
     *
     * @param class-string<Q> $name The query class name
     * @param PreprocessorInterface|null $preprocessor
     *
     * @return Q
     *
     * @template Q as CommandInterface
     */
    public function make(string $name, PreprocessorInterface $preprocessor = null): CommandInterface;

    /**
     * Get the compiler for the given query class
     * If no custom compiler is found for the query, the default one is returned
     *
     * @param class-string<Q> $query The query class name
     *
     * @return object
     *
     * @template Q as \Bdf\Prime\Query\CompilableClause&\Bdf\Prime\Query\Contract\Compilable&CommandInterface
     */
    public function compiler(string $query): object;

    /**
     * Create query object for manually compiled query
     *
     * @param mixed $query The query body, in the current database language. Must be a string for SQL databases
     *
     * @return CompiledQueryInterface
     * @todo uncomment in prime 3.0
     */
    //public function compiled($query): CompiledQueryInterface;
}
