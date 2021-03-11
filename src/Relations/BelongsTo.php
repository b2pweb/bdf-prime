<?php

namespace Bdf\Prime\Relations;

/**
 * BelongsTo
 * 
 * @package Bdf\Prime\Relations
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
