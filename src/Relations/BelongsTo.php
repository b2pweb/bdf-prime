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
    protected function getForeignInfos()
    {
        return [$this->local, $this->localKey];
    }
}
