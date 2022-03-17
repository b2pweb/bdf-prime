<?php

namespace Bdf\Prime\Query\Extension;

use Bdf\Prime\Query\Clause;
use Bdf\Prime\Query\Compiler\CompilerState;
use Bdf\Prime\Query\Contract\Whereable;
use Doctrine\DBAL\Query\Expression\CompositeExpression;

/**
 * Trait for where() method
 *
 * @see Whereable
 * @property CompilerState $compilerState
 *
 * @psalm-require-implements Whereable
 */
trait SimpleWhereTrait
{
    /**
     * {@inheritdoc}
     *
     * @see Whereable::where()
     */
    public function where($column, $operator = null, $value = null)
    {
        if (!is_string($column) && is_callable($column)) {
            $this->nested($column, $operator ?: CompositeExpression::TYPE_AND);
        } else {
            $this->compilerState->invalidate('where');

            $this->buildClause('where', $column, $operator, $value);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @see Whereable::orWhere()
     */
    public function orWhere($column, $operator = null, $value = null)
    {
        if (!is_string($column) && is_callable($column)) {
            $this->nested($column, $operator ?: CompositeExpression::TYPE_OR);
        } else {
            $this->compilerState->invalidate('where');

            $this->buildClause('where', $column, $operator, $value, CompositeExpression::TYPE_OR);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @see Whereable::whereNull()
     */
    public function whereNull(string $column, string $type = CompositeExpression::TYPE_AND)
    {
        $this->compilerState->invalidate('where');

        return $this->buildClause('where', $column, '=', null, $type);
    }

    /**
     * {@inheritdoc}
     *
     * @see Whereable::whereNotNull()
     */
    public function whereNotNull(string $column, string $type = CompositeExpression::TYPE_AND)
    {
        $this->compilerState->invalidate('where');

        return $this->buildClause('where', $column, '!=', null, $type);
    }

    /**
     * {@inheritdoc}
     *
     * @see Whereable::orWhereNull()
     */
    public function orWhereNull(string $column)
    {
        return $this->whereNull($column, CompositeExpression::TYPE_OR);
    }

    /**
     * {@inheritdoc}
     *
     * @see Whereable::orWhereNotNull()
     */
    public function orWhereNotNull(string $column)
    {
        return $this->whereNotNull($column, CompositeExpression::TYPE_OR);
    }

    /**
     * {@inheritdoc}
     *
     * @see Whereable::whereRaw()
     */
    public function whereRaw($raw, string $type = CompositeExpression::TYPE_AND)
    {
        $this->compilerState->invalidate('where');

        return $this->buildRaw('where', $raw, $type);
    }

    /**
     * {@inheritdoc}
     *
     * @see Whereable::orWhereRaw()
     */
    public function orWhereRaw($raw)
    {
        return $this->whereRaw($raw, CompositeExpression::TYPE_OR);
    }

    /**
     * {@inheritdoc}
     *
     * @see Whereable::nested()
     */
    public function nested(callable $callback, string $type = CompositeExpression::TYPE_AND)
    {
        $this->compilerState->invalidate('where');

        return $this->buildNested('where', $callback, $type);
    }

    /**
     * {@inheritdoc}
     *
     * @see Clause::buildClause()
     */
    abstract public function buildClause(string $statement, $expression, $operator = null, $value = null, string $type = CompositeExpression::TYPE_AND);

    /**
     * {@inheritdoc}
     *
     * @see Clause::buildNested()
     */
    abstract public function buildNested(string $statement, callable $callback, string $type = CompositeExpression::TYPE_AND);

    /**
     * {@inheritdoc}
     *
     * @see Clause::buildRaw()
     */
    abstract public function buildRaw(string $statement, $expression, string $type = CompositeExpression::TYPE_AND);
}
