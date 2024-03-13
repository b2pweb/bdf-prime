<?php

namespace Bdf\Prime\ValueObject;

use function is_object;
use function is_scalar;
use function is_string;
use function method_exists;

/**
 * Base class for string value object
 *  Constraints should be added on the constructor of the child class
 *
 * @psalm-immutable
 * @implements ValueObjectInterface<string>
 */
class BaseString implements ValueObjectInterface
{
    /**
     * The internal primitive value
     *
     * @var string
     * @readonly
     */
    /* readonly */ protected string $value;

    /**
     * @param string $value The primitive value
     * @psalm-consistent-constructor
     */
    protected function __construct(string $value)
    {
        $this->value = $value;
    }

    /**
     * {@inheritdoc}
     */
    final public function value(): string
    {
        return $this->value;
    }

    /**
     * {@inheritdoc}
     */
    public static function from($value): ValueObjectInterface
    {
        if (!is_string($value)) {
            throw new ValueObjectTypeError(static::class, 'string', $value);
        }

        return new static($value);
    }

    /**
     * {@inheritdoc}
     */
    public static function tryFrom($value): ?self
    {
        if (!is_scalar($value) && (!is_object($value) || !method_exists($value, '__toString'))) {
            return null;
        }

        try {
            return new static((string) $value);
        } catch (ValueObjectExceptionInterface $e) {
            return null;
        }
    }
}
