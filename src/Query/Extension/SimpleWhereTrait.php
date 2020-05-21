<?php

namespace Bdf\Prime\Query\Extension;

use Bdf\Prime\Query\Clause;
use Bdf\Prime\Query\Contract\Whereable;
use Bdf\Prime\Query\Compiler\CompilerInterface;
use Doctrine\DBAL\Query\Expression\CompositeExpression;

/**
 * Trait for where() method
 *
 * @see Whereable
 * @property CompilerInterface $compiler
 */
trait SimpleWhereTrait
{
    /**
     * @see Whereable::where()
     */
    public function where($column, $operator = null, $value = null)
    {
        if ($column instanceof \Closure) {
            $this->nested($column, $operator ?: CompositeExpression::TYPE_AND);
        } else {
            $this->compilerState->invalidate('where');

            $this->buildClause('where', $column, $operator, $value);
        }

        return $this;
    }

    /**
     * @see Whereable::orWhere()
     */
    public function orWhere($column, $operator = null, $value = null)
    {
        if ($column instanceof \Closure) {
            $this->nested($column, $operator ?: CompositeExpression::TYPE_OR);
        } else {
            $this->compilerState->invalidate('where');

            $this->buildClause('where', $column, $operator, $value, CompositeExpression::TYPE_OR);
        }

        return $this;
    }

    /**
     * @see Whereable::whereNull()
     */
    public function whereNull($column, $type = CompositeExpression::TYPE_AND)
    {
        $this->compilerState->invalidate('where');

        return $this->buildClause('where', $column, '=', null, $type);
    }

    /**
     * @see Whereable::whereNotNull()
     */
    public function whereNotNull($column, $type = CompositeExpression::TYPE_AND)
    {
        $this->compilerState->invalidate('where');

        return $this->buildClause('where', $column, '!=', null, $type);
    }

    /**
     * @see Whereable::orWhereNull()
     */
    public function orWhereNull($column)
    {
        return $this->whereNull($column, CompositeExpression::TYPE_OR);
    }

    /**
     * @see Whereable::orWhereNotNull()
     */
    public function orWhereNotNull($column)
    {
        return $this->whereNotNull($column, CompositeExpression::TYPE_OR);
    }

    /**
     * @see Whereable::whereRaw()
     */
    public function whereRaw($raw, $type = CompositeExpression::TYPE_AND)
    {
        $this->compilerState->invalidate('where');

        return $this->buildRaw('where', $raw, $type);
    }

    /**
     * @see Whereable::orWhereRaw()
     */
    public function orWhereRaw($raw)
    {
        return $this->whereRaw($raw, CompositeExpression::TYPE_OR);
    }

    /**
     * @see Whereable::nested()
     */
    public function nested(\Closure $callback, $type = CompositeExpression::TYPE_AND)
    {
        $this->compilerState->invalidate('where');

        return $this->buildNested('where', $callback, $type);
    }

    /**
     * @see Clause::buildClause()
     */
    abstract public function buildClause($statement, $expression, $operator = null, $value = null, $type = CompositeExpression::TYPE_AND);

    /**
     * @see Clause::buildNested()
     */
    abstract public function buildNested($statement, \Closure $callback, $type = CompositeExpression::TYPE_AND);

    /**
     * @see Clause::buildNested()
     */
    abstract public function buildRaw($statement, $expression, $type = CompositeExpression::TYPE_AND);
}
