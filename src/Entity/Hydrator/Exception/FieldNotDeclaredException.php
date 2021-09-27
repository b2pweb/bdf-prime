<?php

namespace Bdf\Prime\Entity\Hydrator\Exception;

use InvalidArgumentException;

/**
 * Trying to access a field which is not declared on mapper
 */
class FieldNotDeclaredException extends InvalidArgumentException implements HydratorException
{
    /**
     * @param class-string $entity
     * @param string $field
     */
    public function __construct(string $entity, string $field)
    {
        parent::__construct('The field "' . $field . '" is not declared for the entity ' . $entity);
    }
}
