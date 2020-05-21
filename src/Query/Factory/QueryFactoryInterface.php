<?php

namespace Bdf\Prime\Query\Factory;

use Bdf\Prime\Query\CommandInterface;
use Bdf\Prime\Query\Compiler\CompilerInterface;
use Bdf\Prime\Query\Compiler\Preprocessor\PreprocessorInterface;

/**
 * Factory type for create query objects
 */
interface QueryFactoryInterface
{
    /**
     * Make the query
     *
     * <code>
     * $factory->make('myQuery');
     * $factory->make(KeyValueQuery::class);
     * </code>
     *
     * @param string $name The query name, or class name
     * @param PreprocessorInterface $preprocessor
     *
     * @return CommandInterface
     */
    public function make($name, PreprocessorInterface $preprocessor = null);

    /**
     * Get the compiler for the given query class
     * If no custom compiler is found for the query, the default one is returned
     *
     * @param string $query The query class name
     *
     * @return CompilerInterface
     */
    public function compiler($query);
}
