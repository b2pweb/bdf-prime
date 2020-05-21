<?php

namespace Bdf\Prime\Query\Expression;

use Bdf\Prime\Types\TypeInterface;

/**
 * Expression transformer for force converting the value according to the type
 *
 * Might be useful for searching exact value of an array
 *
 * $query->where('roles', new Value([5, 2]));
 * With roles as 'searchable_array', the condition will not be transformed to an IN expression
 */
class Value extends AbstractExpressionTransformer implements TypedExpressionInterface
{
    /**
     * @var TypeInterface
     */
    protected $type;


    /**
     * {@inheritdoc}
     */
    public function getValue()
    {
        return $this->type ?
            $this->type->toDatabase($this->value)
            : $this->value
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function setType(TypeInterface $type)
    {
        $this->type = $type;

        return $this;
    }
}
