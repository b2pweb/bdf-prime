<?php

namespace Bdf\Prime\Query\Closure\Parser;

use Bdf\Prime\Query\Closure\ClassNameResolver;
use Bdf\Prime\Query\Closure\Value\ArrayAccessValue;
use Bdf\Prime\Query\Closure\Value\ArrayValue;
use Bdf\Prime\Query\Closure\Value\ComparisonValueInterface;
use Bdf\Prime\Query\Closure\Value\ConstantValue;
use Bdf\Prime\Query\Closure\Value\GetterValue;
use Bdf\Prime\Query\Closure\Value\PropertyValue;
use Bdf\Prime\Query\Closure\Value\VariableValue;
use PhpParser\Node\Expr;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\DNumber;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Scalar\String_;
use RuntimeException;

use function constant;

/**
 * Parse value of right side of comparison
 *
 * Handles:
 * - int, float or simple string constant e.g. 1, 1.2, 'foo'
 * - constant e.g. PHP_INT_MAX, true
 * - class constant e.g. Foo::BAR
 * - inline array expression e.g. [1, 2, $foo]
 * - array access e.g. $array['foo']
 * - property access e.g. $entity->property
 * - getter call e.g. $entity->getter()
 */
final class ValueParser
{
    private ClassNameResolver $resolver;

    public function __construct(ClassNameResolver $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * Parse the value expression
     *
     * @param Expr $expr
     * @return ComparisonValueInterface
     */
    public function parse(Expr $expr): ComparisonValueInterface
    {
        if ($expr instanceof Expr\Variable) {
            return new VariableValue($expr->name);
        }

        if ($expr instanceof LNumber || $expr instanceof DNumber || $expr instanceof String_) {
            return new ConstantValue($expr->value);
        }

        if ($expr instanceof Expr\ConstFetch) {
            return new ConstantValue(constant($expr->name->toString()));
        }

        if ($expr instanceof Expr\ClassConstFetch) {
            return $this->parseClassConstant($expr);
        }

        if ($expr instanceof Expr\Array_) {
            return $this->parseArray($expr);
        }

        if ($expr instanceof Expr\ArrayDimFetch) {
            return $this->parseArrayAccess($expr);
        }

        if ($expr instanceof Expr\PropertyFetch) {
            return $this->parsePropertyFetch($expr);
        }

        if ($expr instanceof Expr\MethodCall) {
            return $this->parseGetter($expr);
        }

        throw new RuntimeException('Invalid comparison value ' . $expr->getType() . '. Only scalar values, constants, and arrays can be used in filters.');
    }

    private function parseArray(Expr\Array_ $array): ArrayValue
    {
        $values = [];

        foreach ($array->items as $item) {
            $values[] = $this->parse($item->value);
        }

        return new ArrayValue($values);
    }

    private function parseArrayAccess(Expr\ArrayDimFetch $expr): ComparisonValueInterface
    {
        if ($expr->dim === null) {
            throw new RuntimeException('A key must be provided for array access.');
        }

        return new ArrayAccessValue($this->parse($expr->var), $this->parse($expr->dim));
    }

    private function parseClassConstant(Expr\ClassConstFetch $expr): ConstantValue
    {
        if (!$expr->class instanceof Name) {
            throw new RuntimeException('Cannot resolve dynamic class constant. Use actual class name or store the constant into a variable.');
        }

        $className = $this->resolver->resolve($expr->class);
        return new ConstantValue(constant($className . '::' . $expr->name->toString()));
    }

    private function parsePropertyFetch(Expr\PropertyFetch $expr): PropertyValue
    {
        if (!$expr->name instanceof Identifier) {
            throw new RuntimeException('Cannot resolve dynamic property.');
        }

        return new PropertyValue($this->parse($expr->var), $expr->name->toString());
    }

    private function parseGetter(Expr\MethodCall $expr): GetterValue
    {
        if (!$expr->name instanceof Identifier) {
            throw new RuntimeException('Cannot resolve dynamic method name.');
        }

        if ($expr->args) {
            throw new RuntimeException('Cannot use method call with arguments in filters.');
        }

        return new GetterValue($this->parse($expr->var), $expr->name->toString());
    }
}
