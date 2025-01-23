<?php

namespace Bdf\Prime\Bus\Query\ReturnType;

use TypeError;

/**
 * Represents the result type of query
 *
 * It has two purposes:
 * - Ensure that the result is of the expected type. This is performed using a cast, if possible, or by throwing an exception.
 * - Select the repository or the source to use for the query execution, by using the unwrapped type.
 *
 * A custom implementation of this type can be used to handle some post-processing of the query result.
 * In this case, it's recommended that the implementation is pure and does not perform any other request like loading related entities.
 *
 * @template T
 */
interface ReturnTypeInterface
{
    /**
     * Get the inner type.
     *
     * In case of collection, or container type, this method should return the type of the elements.
     * In case of atomic type, this method should return the type itself.
     *
     * This method can use used to resolve the repository to use for the query execution. In this case,
     * this method returns the entity class name.
     *
     * When no unwrapped type is available, or it's not an object type, this method should return null.
     *
     * @return class-string|null
     */
    public function unwrappedType(): ?string;

    /**
     * Cast or ensure the value is of the expected type
     *
     * The implementation should be pure: it should not depend on any external state, and should not perform any other request,
     * like loading related entities.
     *
     * @param mixed $value The raw query result
     * @return T The cast value
     *
     * @throws TypeError If the value is not of the expected type
     */
    public function cast(mixed $value): mixed;
}
