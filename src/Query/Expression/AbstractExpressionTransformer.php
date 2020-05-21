<?php

namespace Bdf\Prime\Query\Expression;

use Bdf\Prime\Query\Compiler\CompilerInterface;

/**
 * AbstractExpressionTransformer
 */
abstract class AbstractExpressionTransformer implements ExpressionTransformerInterface
{
    /**
     * @var mixed
     */
    protected $value;

    /**
     * @var CompilerInterface
     */
    protected $compiler;

    /**
     * @var string
     */
    protected $column;

    /**
     * @var string
     */
    protected $operator;


    /**
     * AbstractExpressionTransformer constructor.
     *
     * @param mixed $value Value to transform
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function setContext(CompilerInterface $compiler, $column, $operator)
    {
        $this->compiler = $compiler;
        $this->column   = $column;
        $this->operator = $operator;
    }

    /**
     * {@inheritdoc}
     */
    public function getOperator()
    {
        return $this->operator;
    }

    /**
     * {@inheritdoc}
     */
    public function getColumn()
    {
        return $this->column;
    }
}
