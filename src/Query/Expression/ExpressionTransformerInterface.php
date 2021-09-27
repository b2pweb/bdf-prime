<?php

namespace Bdf\Prime\Query\Expression;

use Bdf\Prime\Query\Compiler\CompilerInterface;
use Bdf\Prime\Query\Compiler\Preprocessor\OrmPreprocessor;
use Bdf\Prime\Query\QueryInterface;

/**
 * Interface for transform "WHERE expression" with mid level view
 *
 * This expression is higher level than @see ExpressionInterface which can only generate SQL query
 * But it's lower level than basic @see QueryInterface::where() for choosing how to convert the value while allowing binding
 *
 * Expression transformers permits :
 * - An OOP approach for where() condition :
 *      - $query->where('myColumn', (new Like($data))->contains()); instead of $query->where('myColumn', ':like', array_map(function($e) {return "%$e%";}, $data));
 * - Adds control for convert expression value :
 *      - @see OrmPreprocessor::expression()
 *      - $query->where('roles', '=', new Value([5, 2])); instead of $query->where('roles', '=', $attribute->getType()->convertToDatabaseValue([5, 2]));
 * - Handle dynamic column name
 *
 * Expression transformers DO NOT permits :
 * - Generate abstract SQL code
 * - Implements an undefined operator
 * - Go beyond bind()
 */
interface ExpressionTransformerInterface
{
    /**
     * Set the expression context
     *
     * @param CompilerInterface $compiler
     * @param string $column
     * @param string $operator
     *
     * @todo La colonne donnée est une colonne SQL (t0.attr), voir s'il faut gérer sur preprocessor ?
     *
     * @return void
     */
    public function setContext(CompilerInterface $compiler, string $column, string $operator): void;

    /**
     * Transform and get the value, according to the compiler
     *
     * @return string|array The transformed value
     */
    public function getValue();

    /**
     * Get the expression operator
     *
     * @return string The new operator
     */
    public function getOperator(): string;

    /**
     * Get the expression column
     *
     * @return string The new column
     */
    public function getColumn(): string;
}
