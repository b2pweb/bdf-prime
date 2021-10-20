<?php

namespace Bdf\Prime\Types;

/**
 * Handle array types on database
 *
 * Array it's for strict LIST OF VALUES, in general definition, NOT the PHP definition (i.e. table/map)
 *
 * The value is serialized in simple CSV form. The items should not contains a comma ","
 *
 * Searchable array values are ALWAYS surrounded be comma ","
 *
 * To search from database, you can use :
 * - Regex : ".*,$search,.*"
 * - Like : "%,$search,%"
 */
class ArrayType extends AbstractFacadeType
{
    /**
     * {@inheritdoc}
     */
    public function __construct($type = self::TARRAY)
    {
        parent::__construct($type);
    }

    /**
     * {@inheritdoc}
     */
    public function fromDatabase($value, array $fieldOptions = [])
    {
        if (empty($value)) {
            return [];
        }

        $values = [];

        foreach (explode(',', $value) as $item) {
            if ($item !== '') {
                //TODO how to convert value
                $values[] = $item;
            }
        }

        return $values;
    }

    /**
     * {@inheritdoc}
     */
    public function toDatabase($value)
    {
        if ($value === null) {
            return null;
        }

        if (empty($value)) {
            return '';
        }

        if (is_string($value)) {
            return $value;
        }

        return ','.implode(',', $value).',';
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultType()
    {
        return self::TEXT;
    }

    /**
     * {@inheritdoc}
     */
    public function phpType(): string
    {
        return PhpTypeInterface::TARRAY;
    }
}
