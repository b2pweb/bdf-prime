<?php

namespace Bdf\Prime\Entity\Hydrator;

use Bdf\Prime\Entity\Hydrator\Exception\InvalidTypeException;

/**
 * Interface for hydrate entities from an array
 */
interface HydratorInterface
{
    /**
     * Hydrate the entity from an array.
     * If an object is given, set the object,
     * If data is a multi-dimensional array, recursively hydrate entities
     *
     * Note: With typed properties, trying to hydrator a not null property with null will raise an InvalidTypeException
     *
     * @param object $object
     * @param array $data
     *
     * @throws InvalidTypeException If the given type do not correspond with the declared type
     */
    public function hydrate($object, array $data);

    /**
     * Extract attributes from the entity.
     * The result array is multi-dimensional.
     * To get a single-dimension array, use @see MapperHydratorInterface::flatExtract()
     *
     * Note: Uninitialized properties are ignored
     *
     * @param object $object
     * @param string[] $attributes
     *
     * @return array
     */
    public function extract($object, array $attributes = []);
}
