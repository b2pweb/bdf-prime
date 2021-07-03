<?php

namespace Bdf\Prime\Query\Expression;

use Bdf\Prime\Query\CompilableClause;
use Bdf\Prime\Query\Compiler\CompilerInterface;
use Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 * Aggregation expressions
 * Can be use on Query::select() method
 *
 * Usage:
 * <code>
 * User::select([
 *     'firstId' => Aggregate::min(id),
 *     'lastId' => Aggregate::max(id),
 *     'count' => Aggregate::count(id),
 * ]);
 * </code>
 */
abstract class Aggregate implements ExpressionInterface
{
    /**
     * @var string
     */
    private $attribute;

    /**
     * Max constructor.
     *
     * @param string $attribute The attribute name
     */
    public function __construct(string $attribute)
    {
        $this->attribute = $attribute;
    }

    /**
     * {@inheritdoc}
     */
    final public function build(CompilableClause $query, CompilerInterface $compiler)
    {
        return $this->expression(
            $compiler->platform()->grammar(),
            $compiler->quoteIdentifier($query, $query->preprocessor()->field($this->attribute))
        );
    }

    /**
     * Get the aggregate expression
     *
     * @param AbstractPlatform $platform The database platform
     * @param string $attribute The attribute to aggregate
     *
     * @return string
     */
    abstract protected function expression(AbstractPlatform $platform, string $attribute): string;

    /**
     * Perform MIN() aggregation function
     *
     * @param string $attribute The attribute to aggregate
     *
     * @return self
     */
    public static function min(string $attribute): self
    {
        return new class($attribute) extends Aggregate {
            protected function expression(AbstractPlatform $platform, string $attribute): string
            {
                return $platform->getMinExpression($attribute);
            }
        };
    }

    /**
     * Perform MAX() aggregation function
     *
     * @param string $attribute The attribute to aggregate
     *
     * @return self
     */
    public static function max(string $attribute): self
    {
        return new class($attribute) extends Aggregate {
            protected function expression(AbstractPlatform $platform, string $attribute): string
            {
                return $platform->getMaxExpression($attribute);
            }
        };
    }

    /**
     * Perform AVG() aggregation function
     *
     * @param string $attribute The attribute to aggregate
     *
     * @return self
     */
    public static function avg(string $attribute): self
    {
        return new class($attribute) extends Aggregate {
            protected function expression(AbstractPlatform $platform, string $attribute): string
            {
                return $platform->getAvgExpression($attribute);
            }
        };
    }

    /**
     * Perform COUNT() aggregation function
     *
     * @param string $attribute The attribute to aggregate
     *
     * @return self
     */
    public static function count(string $attribute): self
    {
        return new class($attribute) extends Aggregate {
            protected function expression(AbstractPlatform $platform, string $attribute): string
            {
                return $platform->getCountExpression($attribute);
            }
        };
    }

    /**
     * Perform SUM() aggregation function
     *
     * @param string $attribute The attribute to aggregate
     *
     * @return self
     */
    public static function sum(string $attribute): self
    {
        return new class($attribute) extends Aggregate {
            protected function expression(AbstractPlatform $platform, string $attribute): string
            {
                return $platform->getSumExpression($attribute);
            }
        };
    }
}
