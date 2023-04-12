<?php

namespace Bdf\Prime\Query\Closure\Parser;

use Bdf\Prime\Query\Closure\Filter\AndFilter;
use Bdf\Prime\Query\Closure\Filter\AtomicFilter;
use Bdf\Prime\Query\Closure\Filter\OrFilter;
use Bdf\Prime\Query\Closure\Value\ConstantValue;
use Exception;
use PhpParser\Node;
use RuntimeException;

use function count;

/**
 * Parse a boolean expression
 *
 * Handle:
 * - binary operations (e.g. $entity->foo >= 1)
 * - function call (e.g. in_array($entity->foo, [1, 2, 3]))
 * - unary boolean expression (e.g. !$entity->foo)
 * - And expression (e.g. $entity->foo > 5 && $entity->bar < 42)
 * - Or expression (e.g. $entity->foo || $entity->bar > 42)
 */
final class BooleanExpressionParser
{
    private const OPERATORS_MAPPING = [
        '==' => '=',
        '===' => '=',
        '!==' => '!=',
    ];

    private EntityAccessorParser $entityAccessorParser;
    private FunctionCallParser $functionCallParser;
    private ValueParser $valueParser;

    /**
     * @param EntityAccessorParser $entityAccessorParser
     * @param FunctionCallParser $functionCallParser
     * @param ValueParser $valueParser
     */
    public function __construct(EntityAccessorParser $entityAccessorParser, FunctionCallParser $functionCallParser, ValueParser $valueParser)
    {
        $this->entityAccessorParser = $entityAccessorParser;
        $this->functionCallParser = $functionCallParser;
        $this->valueParser = $valueParser;
    }

    /**
     * @param Node\Expr $expr
     * @return AndFilter
     */
    public function parse(Node\Expr $expr): AndFilter
    {
        if ($expr instanceof Node\Expr\BinaryOp\BooleanAnd) {
            return new AndFilter($this->parse($expr->left), $this->parse($expr->right));
        }

        if ($expr instanceof Node\Expr\BinaryOp\BooleanOr) {
            return new AndFilter($this->parseOrExpression($expr));
        }

        if ($expr instanceof Node\Expr\BinaryOp) {
            return new AndFilter($this->parseBinaryOperation($expr));
        }

        if ($expr instanceof Node\Expr\FuncCall) {
            return new AndFilter($this->functionCallParser->parse($expr));
        }

        try {
            return new AndFilter($this->parseUnaryBooleanExpression($expr));
        } catch (Exception $e) {
            throw new RuntimeException('Unsupported expression ' . $expr->getType() . ' in filters. Supported expressions are: binary operations, function calls getter for a boolean, and not expression.');
        }
    }

    private function parseBinaryOperation(Node\Expr\BinaryOp $expr): AtomicFilter
    {
        return new AtomicFilter(
            $this->entityAccessorParser->parse($expr->left),
            self::OPERATORS_MAPPING[$expr->getOperatorSigil()] ?? $expr->getOperatorSigil(),
            $this->valueParser->parse($expr->right),
        );
    }

    private function parseUnaryBooleanExpression(Node\Expr $expr): AtomicFilter
    {
        if ($expr instanceof Node\Expr\BooleanNot) {
            $expr = $expr->expr;
            $value = false;
        } else {
            $value = true;
        }

        // Simple boolean expression
        return new AtomicFilter(
            $this->entityAccessorParser->parse($expr),
            '=',
            new ConstantValue($value),
        );
    }

    private function parseOrExpression(Node\Expr\BinaryOp\BooleanOr $expr): OrFilter
    {
        $left = $this->parse($expr->left);
        $right = $this->parse($expr->right);

        $filters = [];

        if (count($left) === 1 && $left[0] instanceof OrFilter) {
            $filters = $left[0]->filters;
        } else {
            $filters[] = $left;
        }

        if (count($right) === 1 && $right[0] instanceof OrFilter) {
            $filters = [...$filters, ...$right[0]->filters];
        } else {
            $filters[] = $right;
        }

        return new OrFilter($filters);
    }
}
