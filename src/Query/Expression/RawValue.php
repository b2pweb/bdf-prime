<?php

namespace Bdf\Prime\Query\Expression;

/**
 * Bind a raw value into query. Ensure that the value will not be transformed
 *
 * Unlike Raw, the value will be bind (use prepared query)
 * Unlike Value, the value will not be transformed using type
 *
 * <code>
 * // Search for entities created before year 2017
 * $query->where('createdAt', '<', new RawValue('2017'));
 *
 * // Search for entities with exact serialized value (searchable_array here)
 * $query->where('roles', '=', new RawValue(',1,3,'));
 * </code>
 *
 * @see Raw For raw database expression
 * @see Value For bind value
 */
class RawValue extends AbstractExpressionTransformer
{
    /**
     * {@inheritdoc}
     */
    public function getValue()
    {
        return $this->value;
    }
}
