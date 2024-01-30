<?php

namespace Bdf\Prime\ValueObject;

/**
 * Base type for defining a value object
 *
 * The implementation of this interface must be immutable,
 * and instances must be created through the from() or tryFrom() methods.
 *
 * Constructor should not be public, and should be used only for internal purpose.
 *
 * @template T
 * @psalm-immutable
 */
interface ValueObjectInterface
{
    /**
     * Get the inner primitive value
     *
     * @return T
     */
    public function value();

    /**
     * Create a new instance from the given primitive value
     * If the type of the value is not valid, or the value is not valid, an exception must be thrown
     *
     * @param R $value The primitive value
     * @return static<R>
     *
     * @throws ValueObjectExceptionInterface When the value is not valid
     * @template R
     */
    public static function from($value): self;

    /**
     * Try to create a new instance from the given primitive value, or return null if the value is not valid
     * Unlike the from() method, this method must not throw any exception, and should cast the value if needed
     *
     * This method can be implemented as:
     * <code>
     *     public static function tryFrom($value): ?self
     *     {
     *         try {
     *             return static::from((int) $value);
     *         } catch (ValueObjectExceptionInterface $e) {
     *             return null;
     *         }
     *     }
     * </code>
     *
     * @param mixed $value The primitive value
     *
     * @return static|null The value object, or null if the value is not valid
     */
    public static function tryFrom($value): ?self;
}
