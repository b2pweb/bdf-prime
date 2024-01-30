<?php

namespace Bdf\Prime\ValueObject;

use function is_int;
use function is_scalar;

/**
 * Base class for integer value object
 * Constraints should be added on the constructor of the child class
 *
 * @psalm-immutable
 * @implements ValueObjectInterface<int>
 */
class BaseInteger implements ValueObjectInterface
{
    /**
     * The internal primitive value
     *
     * @var int
     * @readonly
     */
    /* readonly */ protected int $value;

    /**
     * @param int $value The primitive value
     * @psalm-consistent-constructor
     */
    protected function __construct(int $value)
    {
        $this->value = $value;
    }

    /**
     * {@inheritdoc}
     */
    final public function value(): int
    {
        return $this->value;
    }

    /**
     * {@inheritdoc}
     */
    public static function from($value): ValueObjectInterface
    {
        if (!is_int($value)) {
            throw new ValueObjectTypeError(static::class, 'int', $value);
        }

        return new static($value);
    }

    /**
     * {@inheritdoc}
     */
    public static function tryFrom($value): ?self
    {
        if (!is_scalar($value)) {
            return null;
        }

        try {
            return new static((int) $value);
        } catch (ValueObjectExceptionInterface $e) {
            return null;
        }
    }
}
