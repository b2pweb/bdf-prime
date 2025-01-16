<?php

namespace Bdf\Prime\Bus;

use Attribute;

/**
 * @todo doc
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class FromRelation
{
    public function __construct(
        public readonly string $relation,
    ) {}
}
