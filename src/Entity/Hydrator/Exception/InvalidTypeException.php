<?php

namespace Bdf\Prime\Entity\Hydrator\Exception;

use Bdf\Prime\Entity\Hydrator\MapperHydratorInterface;
use InvalidArgumentException;
use TypeError;

/**
 * Wrap TypeError exception
 * Raised when trying to hydrate a property with an invalid type (like null on non-null property)
 *
 * @see MapperHydratorInterface::hydrateOne()
 */
class InvalidTypeException extends InvalidArgumentException implements HydratorException
{
    /**
     * @param TypeError $previous
     * @param string|null $mapperType The type declared on mapper
     */
    public function __construct(TypeError $previous, ?string $mapperType = null)
    {
        parent::__construct(
            'Try to hydrate with an invalid type : '.$previous->getMessage().($mapperType ? ' (declared type on mapper : '.$mapperType.')' : ''),
            0,
            $previous
        );
    }
}
