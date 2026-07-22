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
    public function __construct(string|ExpressionInterface|null $field = null, bool $escape = true, bool $skipNull = true, bool $skipEmpty = false)
    {
        parent::__construct($field, true, false, false, $escape, null, null, $skipNull, $skipEmpty);
    }
}
