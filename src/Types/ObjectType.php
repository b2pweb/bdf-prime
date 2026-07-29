<?php

namespace Bdf\Prime\Types;

/**
 * Map to stdClass object
 */
final class ObjectType extends AbstractSerializeType
{
    /**
     * {@inheritdoc}
     */
    public function __construct(string $type = self::OBJECT)
    {
        parent::__construct($type);
    }

    /**
     * {@inheritdoc}
     */
    public function phpType(): string
    {
        return PhpTypeInterface::OBJECT;
    }

    /**
     * {@inheritdoc}
     */
    public function toDatabase(mixed $value): ?string
    {
        if (!is_object($value)) {
            return $value;
        }

        return parent::toDatabase($value);
    }
}
