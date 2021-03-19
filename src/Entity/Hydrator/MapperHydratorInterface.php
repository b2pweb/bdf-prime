<?php

namespace Bdf\Prime\Entity\Hydrator;

use Bdf\Prime\Entity\Instantiator\InstantiatorInterface;
use Bdf\Prime\Mapper\Mapper;
use Bdf\Prime\Mapper\Metadata;
use Bdf\Prime\Platform\PlatformTypesInterface;

/**
 * Hydrator used by @see Mapper
 *
 * Do not forget to call @see MapperHydratorInterface::setMetadata()
 * Do not forget to call @see MapperHydratorInterface::setInstantiator()
 */
interface MapperHydratorInterface
{
    /**
     * Register the prime entity instantiator
     *
     * @param InstantiatorInterface $instantiator
     */
    public function setPrimeInstantiator(InstantiatorInterface $instantiator);

    /**
     * Register the prime metadata for the hydrator
     *
     * @param Metadata $metadata
     */
    public function setPrimeMetadata(Metadata $metadata);

    /**
     * Extract attributes from the entity. The result array if a single dimensional array.
     * To extract with multi-dimensional array, use @see HydratorInterface::extract()
     *
     * @param object $object The entity to extract
     * @param array<string, mixed> $attributes Attributes to extract. The attribute name is the array key, the value is the metadata
     *
     * @return array
     */
    public function flatExtract($object, array $attributes = null);

    /**
     * Hydrate the entity from a single-dimension array.
     *
     * /!\ This method IS NOT the inverted operation of flatExtract().
     *     This method fill from DATABASE FIELDS, whereas flatExtract() extract using ATTRIBUTES
     *
     * @param object $object
     * @param array $data
     * @param PlatformTypesInterface $types
     */
    public function flatHydrate($object, array $data, PlatformTypesInterface $types);

    /**
     * Extract one attribute value from $object
     *
     * This method will extract values from declared attributes, embedded, or attributes of embedded
     * Only attributes stored into database fields can be extracted
     *
     * If the attribute is an embedded attribute, and one of its ascendant is null, this method will return null
     *
     * @param object $object
     * @param string $attribute
     *
     * @return mixed
     *
     * @throws \InvalidArgumentException When given attribute is not declared
     *
     * @see MapperHydratorInterface::hydrateOne() For perform the reverse operation
     */
    public function extractOne($object, $attribute);

    /**
     * Hydrate one attribute value of $object
     *
     * This method will write to declared attributes, or embedded, or attributes of embedded
     * Only attributes stored into database can be hydrated
     *
     * If the attribute is an embedded attribute, all null ascendant will be instantiated
     *
     * @param object $object
     * @param string $attribute
     * @param mixed $value
     *
     * @throws \InvalidArgumentException When given attribute is not declared or the owner object cannot be instantiated
     *
     * @see MapperHydratorInterface::extractOne() For perform the reverse operation
     */
    public function hydrateOne($object, $attribute, $value);
}
