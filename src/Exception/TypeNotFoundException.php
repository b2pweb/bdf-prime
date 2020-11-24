<?php

namespace Bdf\Prime\Exception;

/**
 * Exception raised when an unknown type is requested
 */
class TypeNotFoundException extends TypeException
{
    /**
     * @param string $type
     */
    public function __construct(string $type)
    {
        parent::__construct($type, 'Type "'.$type.'" cannot be found. Did you register it on registry ?');
    }
}
