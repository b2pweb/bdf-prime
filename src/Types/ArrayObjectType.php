<?php

namespace Bdf\Prime\Types;

/**
 * Map to associative array object
 */
class ArrayObjectType extends AbstractSerializeType
{
    /**
     * {@inheritdoc}
     */
    public function __construct($type = self::ARRAY_OBJECT)
    {
        parent::__construct($type);
    }

    /**
     * {@inheritdoc}
     */
    public function phpType(): string
    {
        return PhpTypeInterface::TARRAY;
    }

    /**
     * {@inheritdoc}
     */
    public function toDatabase($value)
    {
        // Serialized type is string
        // The passed value should be an array,
        // But is the value is a string, we can consider it as serialized value
        if (is_string($value)) {
            return $value;
        }

        return parent::toDatabase($value);
    }
}
