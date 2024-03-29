<?php

namespace Bdf\Prime\Schema\Adapter;

use Bdf\Prime\Schema\IndexInterface;

/**
 * Provide type and composite predicates
 */
abstract class AbstractIndex implements IndexInterface
{
    /**
     * {@inheritdoc}
     */
    public function unique(): bool
    {
        return ($this->type() & self::TYPE_UNIQUE) === self::TYPE_UNIQUE;
    }

    /**
     * {@inheritdoc}
     */
    public function primary(): bool
    {
        return ($this->type() & self::TYPE_PRIMARY) === self::TYPE_PRIMARY;
    }

    /**
     * {@inheritdoc}
     */
    public function isComposite(): bool
    {
        return count($this->fields()) > 1;
    }
}
