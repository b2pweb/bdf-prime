<?php

namespace Bdf\Prime\Relations;

/**
 * BelongsTo
 *
 * @package Bdf\Prime\Relations
 *
 * @template L as object
 * @template R as object
 *
 * @extends HasOne<L, R>
 */
class BelongsTo extends HasOne
{
    /**
     * {@inheritdoc}
     */
    protected function getForeignInfos(): array
    {
        return [$this->local, $this->localKey];
    }
}
