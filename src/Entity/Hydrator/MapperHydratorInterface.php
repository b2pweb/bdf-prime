<?php

namespace Bdf\Prime\Entity\Hydrator;

use Bdf\Prime\Entity\Hydrator\Exception\FieldNotDeclaredException;
use Bdf\Prime\Entity\Hydrator\Exception\InvalidTypeException;
use Bdf\Prime\Entity\Hydrator\Exception\UninitializedPropertyException;
use Bdf\Prime\Entity\Instantiator\InstantiatorInterface;
use Bdf\Prime\Mapper\Mapper;
use Bdf\Prime\Mapper\Metadata;
use Bdf\Prime\Platform\PlatformTypesInterface;

/**
 * Hydrator used by @see Mapper
 *
 * Do not forget to call @see MapperHydratorInterface::setMetadata()
 * Do not forget to call @see MapperHydratorInterface::setInstantiator()
 *
 * @template E as object
 */
interface MapperHydratorInterface
{
    /**
     * Register the prime entity instantiator
     *
     * @param InstantiatorInterface $instantiator
     *
     * @return void
     */
    public function setPrimeInstantiator(InstantiatorInterface $instantiator): void;

    /**
     * Register the prime metadata for the hydrator
     *
     * @param Metadata $metadata
     *
     * @return void
     */
    public function setPrimeMetadata(Metadata $metadata): void;

    /**
     * Extract attributes from the entity. The result array if a single dimensional array.
     * To extract with multi-dimensional array, use @see HydratorInterface::extract()
     *
     * Note: If the entity has typed properties, this method will raised an UninitializedPropertyException if some properties are not initialized
     *
     * @param E $object The entity to extract
     * @param array<string, mixed> $attributes Attributes to extract. The attribute name is the array key, the value is the metadata
     *
     * @return array
     *
     * @throws UninitializedPropertyException When unititialized properties are tried to be extracted
     */
    public function flatExtract($object, ?array $attributes = null): array;

    /**
     * Hydrate the entity from a single-dimension array.
     *
     * /!\ This method IS NOT the inverted operation of flatExtract().
     *     This method fill from DATABASE FIELDS, whereas flatExtract() extract using ATTRIBUTES
     *
     * Note: This method will ignore null values if properties are not nullable.
     *       So those properties will keep their default value or uninitialized state
     *
     * @param E $object
     * @param array $data
     * @param PlatformTypesInterface $types
     *
     * @return void
     *
     * @throws InvalidTypeException When database type do not correspond with property type (expect null)
     */
    public function flatHydrate($object, array $data, PlatformTypesInterface $types): void;

    /**
     * Extract one attribute value from $object
     *
     * This method will extract values from declared attributes, embedded, or attributes of embedded
     * Only attributes stored into database fields can be extracted
     *
     * If the attribute is an embedded attribute, and one of its ascendant is null, this method will return null
     *
     * @param E $object
     * @param string $attribute
     *
     * @return mixed
     *
     * @throws FieldNotDeclaredException When given attribute is not declared
     * @throws UninitializedPropertyException When unititialized properties are tried to be extracted
     *
     * @see MapperHydratorInterface::hydrateOne() For perform the reverse operation
     */
    public function extractOne($object, string $attribute);

    /**
     * Hydrate one attribute value of $object
     *
     * This method will write to declared attributes, or embedded, or attributes of embedded
     * Only attributes stored into database can be hydrated
     *
     * If the attribute is an embedded attribute, all null ascendant will be instantiated
     *
     * @param E $object
     * @param string $attribute
     * @param mixed $value
     *
     * @return void
     *
     * @throws FieldNotDeclaredException When given attribute is not declared or the owner object cannot be instantiated
     * @throws InvalidTypeException When trying to hydrate null on nonnull property
     *
     * @see MapperHydratorInterface::extractOne() For perform the reverse operation
     */
    public function hydrateOne($object, string $attribute, $value): void;
}
