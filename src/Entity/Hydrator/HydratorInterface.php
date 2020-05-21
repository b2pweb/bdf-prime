<?php

namespace Bdf\Prime\Entity\Hydrator;

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
     * @param object $object
     * @param array $data
     */
    public function hydrate($object, array $data);

    /**
     * Extract attributes from the entity.
     * The result array is multi-dimensional.
     * To get a single-dimension array, use @see MapperHydratorInterface::flatExtract()
     *
     * @param object $object
     * @param string[] $attributes
     *
     * @return array
     */
    public function extract($object, array $attributes = []);
}