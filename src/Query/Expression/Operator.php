<?php

namespace Bdf\Prime\Query\Expression;

use Bdf\Prime\Platform\PlatformInterface;
use Bdf\Prime\Query\Compiler\CompilerInterface;

use Bdf\Prime\Types\TypeInterface;

use function count;
use function is_array;

/**
 * Wrap an operator as expression object
 * It can be used on Criteria setters to specify the operator without concatenation with the column name
 */
final class Operator implements ExpressionTransformerInterface, TypedExpressionInterface
{
    private string $operator;
    private $value;
    private string $column;
    private ?TypeInterface $type = null;
    private ?PlatformInterface $platform = null;

    /**
     * Prefer use of static methods
     *
     * @param string $operator Comparison operator. Should be one of operators handled by compiler, and not a raw SQL one
     * @param mixed $value The value to compare
     */
    public function __construct(string $operator, $value)
    {
        $this->operator = $operator;
        $this->value = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function setType(TypeInterface $type): void
    {
        $this->type = $type;
    }

    /**
     * {@inheritdoc}
     */
    public function setContext(object $compiler, string $column, string $operator): void
    {
        $this->column = $column;

        if ($compiler instanceof CompilerInterface) {
            $this->platform = $compiler->platform();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getValue()
    {
        if (!$this->type && !$this->platform) {
            return $this->value;
        }

        $type = $this->type ?? $this->platform->types();

        if (!is_array($this->value)) {
            return $type->toDatabase($this->value);
        }

        return array_map([$type, 'toDatabase'], $this->value);
    }

    /**
     * {@inheritdoc}
     */
    public function getOperator(): string
    {
        return $this->operator;
    }

    /**
     * {@inheritdoc}
     */
    public function getColumn(): string
    {
        return $this->column;
    }

    /**
     * Create an operator using the operator name as method name
     * If multiple arguments are passed, they will be used as the value (value will be an array)
     *
     * @param string $operator
     * @param list<mixed> $arguments
     *
     * @return self
     */
    public static function __callStatic(string $operator, array $arguments): self
    {
        return new self($operator, count($arguments) === 1 ? $arguments[0] : $arguments);
    }

    /**
     * Create a less than `<` operator
     *
     * @param scalar $value Comparison value
     *
     * @return self
     */
    public static function lessThan($value): self
    {
        return new self('<', $value);
    }

    /**
     * Create a less than or equal `<=` operator
     *
     * @param scalar $value Comparison value
     *
     * @return self
     */
    public static function lessThanOrEqual($value): self
    {
        return new self('<=', $value);
    }

    /**
     * Create a greater than `>` operator
     *
     * @param scalar $value Comparison value
     *
     * @return self
     */
    public static function greaterThan($value): self
    {
        return new self('>', $value);
    }

    /**
     * Create a greater than or equal `>=` operator
     *
     * @param scalar $value Comparison value
     *
     * @return self
     */
    public static function greaterThanOrEqual($value): self
    {
        return new self('>=', $value);
    }

    /**
     * Create a regex `~=` operator
     *
     * @param string $pattern Regex pattern
     *
     * @return self
     */
    public static function regex(string $pattern): self
    {
        return new self('~=', $pattern);
    }

    /**
     * Create a like operator
     *
     * @param string $pattern Like pattern
     *
     * @return self
     *
     * @see Like for a dedicated API
     */
    public static function like(string $pattern): self
    {
        return new self(':like', $pattern);
    }

    /**
     * Create a not like `!like` operator
     *
     * @param string $pattern Like pattern
     *
     * @return self
     */
    public static function notlike(string $pattern): self
    {
        return new self('!like', $pattern);
    }

    /**
     * Create in operator
     *
     * @param mixed ...$values
     *
     * @return self
     */
    public static function in(...$values): self
    {
        return new self('in', $values);
    }

    /**
     * Create not in operator
     *
     * @param mixed ...$values
     *
     * @return self
     */
    public static function notIn(...$values): self
    {
        return new self('!in', $values);
    }

    /**
     * Create between operator
     *
     * @param scalar $min Minimum value
     * @param scalar $max Maximum value
     *
     * @return self
     */
    public static function between($min, $max): self
    {
        return new self('between', [$min, $max]);
    }

    /**
     * Create not between operator
     *
     * @param scalar $min Minimum value
     * @param scalar $max Maximum value
     *
     * @return self
     */
    public static function notBetween($min, $max): self
    {
        return new self('!between', [$min, $max]);
    }

    /**
     * Create not equal `!=` operator
     *
     * @param mixed $value Comparison value
     *
     * @return self
     */
    public static function notEqual($value): self
    {
        return new self('!=', $value);
    }
}
