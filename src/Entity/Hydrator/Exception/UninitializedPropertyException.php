<?php

namespace Bdf\Prime\Entity\Hydrator\Exception;

use Bdf\Prime\Entity\Hydrator\MapperHydratorInterface;
use Error;
use RuntimeException;

/**
 * Exception raised when trying to access a not initialized typed property
 * To fix this error, you should set a value (i.e. call the setter), or define a default value on property declaration
 *
 * @see MapperHydratorInterface::extractOne()
 * @see MapperHydratorInterface::flatExtract()
 */
class UninitializedPropertyException extends RuntimeException implements HydratorException
{
    /**
     * @param class-string $className
     * @param string $propertyName
     *
     * @param Error|null $previous
     */
    public function __construct(string $className, string $propertyName, ?Error $previous = null)
    {
        parent::__construct(
            'Trying to read the property '.$className.'::'.$propertyName.' which is not yet initialized. Maybe you have forgot to call the setter or define a default value on the property declaration ?',
            0,
            $previous
        );
    }
}
