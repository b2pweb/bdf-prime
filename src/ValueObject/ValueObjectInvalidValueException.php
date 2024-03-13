<?php

namespace Bdf\Prime\ValueObject;

use LogicException;

/**
 * Error thrown when the value of the value object is not valid
 */
class ValueObjectInvalidValueException extends LogicException implements ValueObjectExceptionInterface
{
    /**
     * @var class-string<ValueObjectInterface>
     */
    private string $valueObjectClass;

    /**
     * @param class-string<ValueObjectInterface> $valueObjectClass
     */
    public function __construct(string $valueObjectClass, string $message)
    {
        $this->valueObjectClass = $valueObjectClass;

        parent::__construct('Invalid value for '.$this->valueObjectClass.': '.$message);
    }

    /**
     * {@inheritdoc}
     */
    public function type(): string
    {
        return $this->valueObjectClass;
    }
}
