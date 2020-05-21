<?php

namespace Bdf\Prime\Types;

/**
 * Facade type using serialize
 */
abstract class AbstractSerializeType extends AbstractFacadeType
{
    /**
     * {@inheritdoc}
     */
    public function toDatabase($value)
    {
        if ($value === null) {
            return null;
        }

        return serialize($value);
    }

    /**
     * {@inheritdoc}
     */
    public function fromDatabase($value, array $fieldOptions = [])
    {
        if ($value === null) {
            return null;
        }

        return unserialize($value);
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultType()
    {
        return self::TEXT;
    }
}
