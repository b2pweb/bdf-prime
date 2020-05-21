<?php

namespace Bdf\Prime\Types;

/**
 * Facade boolean type for database
 */
class BooleanType extends AbstractFacadeType
{
    /**
     * {@inheritdoc}
     */
    public function __construct($type = self::BOOLEAN)
    {
        parent::__construct($type);
    }

    /**
     * {@inheritdoc}
     *
     * @todo Tester value is string ?
     */
    public function toDatabase($value)
    {
        if ($value === null) {
            return null;
        }

        return $value ? 'true' : 'false';
    }

    /**
     * {@inheritdoc}
     */
    public function fromDatabase($value, array $fieldOptions = [])
    {
        if ($value === null) {
            return null;
        }

        if ($value == 'true' || $value == 1) {
            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultType()
    {
        return self::STRING;
    }

    /**
     * {@inheritdoc}
     */
    public function phpType()
    {
        return PhpTypeInterface::BOOLEAN;
    }
}
