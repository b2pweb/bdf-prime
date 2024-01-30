<?php

namespace Bdf\Prime\ValueObject;

use TypeError;

use function get_debug_type;

/**
 * Error thrown when the primitive type of the value object is not valid
 */
class ValueObjectTypeError extends TypeError implements ValueObjectExceptionInterface
{
    /**
     * @var class-string<ValueObjectInterface>
     */
    private string $valueObjectClass;
    private string $expectedType;
    private string $actualType;

    /**
     * @param class-string<ValueObjectInterface> $valueObjectClass The value object class
     * @param string $expectedType The expected primitive type for the value object
     * @param mixed $value The value passed as argument
     */
    public function __construct(string $valueObjectClass, string $expectedType, $value)
    {
        $this->valueObjectClass = $valueObjectClass;
        $this->expectedType = $expectedType;
        $this->actualType = get_debug_type($value);

        parent::__construct("Value object {$this->valueObjectClass} expected to be of type {$this->expectedType}, {$this->actualType} given");
    }

    /**
     * {@inheritdoc}
     */
    public function type(): string
    {
        return $this->valueObjectClass;
    }

    /**
     * The expected primitive type
     *
     * @return string
     */
    public function expectedPrimitiveType(): string
    {
        return $this->expectedType;
    }

    /**
     * The actual primitive type
     *
     * @return string
     */
    public function actualPrimitiveType(): string
    {
        return $this->actualType;
    }
}
