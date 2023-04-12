<?php

namespace Bdf\Prime\Query\Closure\Parser;

use PhpParser\Node\Expr;
use PhpParser\Node\Identifier;
use RuntimeException;

use function lcfirst;
use function str_starts_with;
use function substr;

/**
 * Parser for left operand of a comparison
 * The value must be extracted from the entity parameter
 *
 * Handles:
 * - $entity->property
 * - $entity->getter()
 * - $entity->subEntity->property
 */
final class EntityAccessorParser
{
    private string $parameterName;

    /**
     * @param string $parameterName Name of the entity parameter
     */
    public function __construct(string $parameterName)
    {
        $this->parameterName = $parameterName;
    }

    /**
     * @param Expr $expr Left operand expression
     * @return string The property path separated by dots. Ex: "property", "subEntity.property"
     */
    public function parse(Expr $expr): string
    {
        switch (true) {
            case $expr instanceof Expr\PropertyFetch:
                return $this->parsePropertyFetch($expr);

            case $expr instanceof Expr\MethodCall:
                return $this->parseGetterCall($expr);

            default:
                throw new RuntimeException('Invalid entity accessor ' . $expr->getType() . '. Only properties and getters can be used in filters.');
        }
    }

    private function parsePropertyFetch(Expr\PropertyFetch $propertyFetch): string
    {
        if (!$propertyFetch->name instanceof Identifier) {
            throw new RuntimeException('Dynamic property access is not allowed in filters');
        }

        $name = $propertyFetch->name->name;

        if ($propertyFetch->var instanceof Expr\Variable) {
            $this->checkLeftOperandVariable($propertyFetch->var);

            return $name;
        }

        return $this->parse($propertyFetch->var) . '.' . $name;
    }

    private function parseGetterCall(Expr\MethodCall $expr): string
    {
        if ($expr->args) {
            throw new RuntimeException('Only getters can be used in filters');
        }

        if (!$expr->name instanceof Identifier) {
            throw new RuntimeException('Dynamic method name is not allowed in filters');
        }

        $name = $expr->name->name;

        if (str_starts_with($name, 'get')) {
            $name = lcfirst(substr($name, 3));
        }

        if ($expr->var instanceof Expr\Variable) {
            $this->checkLeftOperandVariable($expr->var);

            return $name;
        }

        return $this->parse($expr->var) . '.' . $name;
    }

    private function checkLeftOperandVariable(Expr\Variable $variable): void
    {
        if ($variable->name !== $this->parameterName) {
            throw new RuntimeException('The left operand of a comparison must be a property or a getter of the entity.');
        }
    }
}
