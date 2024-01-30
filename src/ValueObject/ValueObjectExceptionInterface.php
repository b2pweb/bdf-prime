<?php

namespace Bdf\Prime\ValueObject;

use Throwable;

/**
 * Base error for value object conversion
 */
interface ValueObjectExceptionInterface extends Throwable
{
    /**
     * The value object class which throw the exception
     *
     * @return class-string<ValueObjectInterface>
     */
    public function type(): string;
}
