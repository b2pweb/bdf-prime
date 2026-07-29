<?php

namespace Bdf\Prime\Query\Criteria;

use Attribute;
use Bdf\Prime\Query\Expression\ExpressionInterface;

/**
 * @psalm-immutable
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
readonly class StartsWithCriterion extends LikeCriterion
{
    /**
     * @param string|ExpressionInterface|null $field
     * @param bool $escape
     * @param bool $skipNull
     */
    public function __construct(string|ExpressionInterface|null $field = null, bool $escape = true, bool $skipNull = true)
    {
        parent::__construct($field, true, false, false, $escape, null, null, $skipNull);
    }
}
