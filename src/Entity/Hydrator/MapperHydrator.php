<?php

namespace Bdf\Prime\Entity\Hydrator;

use Bdf\Prime\Entity\Hydrator\Exception\FieldNotDeclaredException;
use Bdf\Prime\Entity\Hydrator\Exception\InvalidTypeException;
use Bdf\Prime\Entity\Hydrator\Exception\UninitializedPropertyException;
use Bdf\Prime\Entity\Instantiator\InstantiatorInterface;
use Bdf\Prime\Mapper\Metadata;
use Bdf\Prime\Platform\PlatformTypesInterface;
use Error;
use ReflectionException;
use ReflectionProperty;
use stdClass;
use TypeError;

/**
 * Base implementation for @see MapperHydratorInterface
 *
 * Prefer use generated hydrators on production
 */
class MapperHydrator implements MapperHydratorInterface
{
    /**
     * @var InstantiatorInterface
     */
    protected $instantiator;

    /**
     * @var Metadata
     */
    protected $metadata;

    /**
     * Property accessors, indexed by attribute name
     *
     * @var ReflectionProperty[][]
     */
    private $reflectionProperties = [];

    /**
     * {@inheritdoc}
     */
    public function setPrimeInstantiator(InstantiatorInterface $instantiator): void
    {
        $this->instantiator = $instantiator;
    }

    /**
     * {@inheritdoc}
     */
    public function setPrimeMetadata(Metadata $metadata): void
    {
        $this->metadata = $metadata;
    }

    /**
     * {@inheritdoc}
     */
    public function flatExtract($object, array $attributes = null): array
    {
        $values = [];
        $cache  = [];

        $attributes = $attributes === null
            ? $this->metadata->attributes
            : array_intersect_key($this->metadata->attributes, $attributes);

        foreach ($attributes as $attribute => $metadata) {
            if (isset($metadata['embedded'])) {
                $path = $metadata['embedded'];

                if (empty($cache[$path])) {
                    $cache[$path] = $this->getOwnerObject($object, $metadata);
                }

                $value = $cache[$path] ? $this->readFromAttribute($cache[$path], $metadata) : null;
            } else {
                $value = $this->readFromAttribute($object, $metadata);
            }

            $valueObjectClass = $metadata['valueObject'] ?? null;

            if ($valueObjectClass && $value instanceof $valueObjectClass) {
                $value = $value->value();
            }

            $values[$attribute] = $value;
        }

        return $values;
    }

    /**
     * {@inheritdoc}
     */
    public function flatHydrate($object, array $data, PlatformTypesInterface $types): void
    {
        $metadata  = $this->metadata->fields;
        $embeddeds = $this->metadata->embeddeds;
        $cacheEmbedded = [
            'root' => $object
        ];

        foreach ($data as $field => $value) {
            $fieldMetadata = $metadata[$field] ?? null;

            if (!isset($fieldMetadata)) {
                continue;
            }

            $value = $types->get($fieldMetadata['type'])->fromDatabase($value, $fieldMetadata['phpOptions']);
            $valueObjectClass = $fieldMetadata['valueObject'] ?? null;

            if ($valueObjectClass && $value !== null) {
                $value = $valueObjectClass::from($value);
            }

            if (isset($fieldMetadata['embedded'])) {
                $path = $fieldMetadata['embedded'];

                //creation du cache d'objet. Le but est de parcourir les paths de l'embedded
                //de creer les objets et de les associés entre eux
                //ex:
                //  'root.wrapper.offer.id'  id est embedded dans offer qui est embedded dans wrapper, etc...
                //   l'attribute id peut etre parsé sans que wrapper ne soit déjà construit (parsqu'il n'a pas d'attribut,
                //   ou parce qu'il est définit avant dans le select)
                if (empty($cacheEmbedded[$path])) {
                    for ($i = 0, $l = count($embeddeds[$path]['paths']); $i < $l; $i++) {
                        $pathCursor = $embeddeds[$path]['paths'][$i];

                        if (empty($cacheEmbedded[$pathCursor])) {
                            $parentMeta = $embeddeds[$pathCursor];

                            if (empty($parentMeta['polymorph'])) {
                                $cacheEmbedded[$pathCursor] = $this->instantiator->instantiate($parentMeta['class'], $parentMeta['hint']);
                            } else {
                                $cacheEmbedded[$pathCursor] = $this->instantiator->instantiate(
                                    $className = $parentMeta['class_map'][$data[$parentMeta['discriminator_field']]],
                                    $parentMeta['hints'][$className]
                                );
                            }

                            $this->writeToEmbedded(
                                $cacheEmbedded[$parentMeta['parentPath']],
                                $parentMeta,
                                $cacheEmbedded[$parentMeta['path']],
                                true
                            );
                        }
                    }
                }

                $this->writeToAttribute($cacheEmbedded[$path], $fieldMetadata, $value, true);
            } else {
                $this->writeToAttribute($object, $fieldMetadata, $value, true);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function extractOne($object, string $attribute)
    {
        $attributeMetadata = $this->metadata->attributes[$attribute] ?? null;

        if ($attributeMetadata === null) {
            if (!isset($this->metadata->embeddeds[$attribute])) {
                throw new FieldNotDeclaredException($this->metadata->entityClass, $attribute);
            }

            return $this->readFromEmbedded($object, $this->metadata->embeddeds[$attribute]);
        } else {
            $ownerObject = $this->getOwnerObject($object, $attributeMetadata);

            // Polymorphic embedded not instantiated
            if ($ownerObject === null) {
                return null;
            }

            $value = $this->readFromAttribute($ownerObject, $attributeMetadata);

            $valueObjectClass = $attributeMetadata['valueObject'] ?? null;

            if ($valueObjectClass && $value instanceof $valueObjectClass) {
                $value = $value->value();
            }

            return $value;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function hydrateOne($object, string $attribute, $value): void
    {
        $attributeMetadata = $this->metadata->attributes[$attribute] ?? null;

        if ($attributeMetadata === null) {
            if (!isset($this->metadata->embeddeds[$attribute])) {
                throw new FieldNotDeclaredException($this->metadata->entityClass, $attribute);
            }

            $this->writeToEmbedded($object, $this->metadata->embeddeds[$attribute], $value, false);
        } else {
            $ownerObject = $this->getOwnerObject($object, $attributeMetadata);

            // Polymorphic embedded not instantiated
            if ($ownerObject === null) {
                throw new \InvalidArgumentException('Cannot write to attribute '.$attribute.' : the embedded entity cannot be resolved');
            }

            $valueObjectClass = $attributeMetadata['valueObject'] ?? null;

            if ($valueObjectClass && $value !== null && !$value instanceof $valueObjectClass) { // Allow hydrate directly the value object
                $value = $valueObjectClass::from($value);
            }

            $this->writeToAttribute($ownerObject, $attributeMetadata, $value, false);
        }
    }

    /**
     * Get owner object attribute
     *
     * @param object $entity
     * @param array $metadata Metadata d'un attribut a retrouver
     *
     * @return object|null The object, or null if cannot be instantiated (polymorph)
     */
    protected function getOwnerObject($entity, array $metadata)
    {
        if (!isset($metadata['embedded'])) {
            return $entity;
        }

        $embeddeds = $this->metadata->embeddeds;
        $current = $entity;
        $embeddedMeta = $embeddeds[$metadata['embedded']];
        $embedded = null;

        //parcourt des paths pour descendre juqu'à l'objet propriétaire des meta données
        //si l'objet n'existe pas, on le créé avant de l'associer à son object parent
        for ($i = 0, $l = count($embeddedMeta['paths']); $i < $l; $i++) {
            $parentMeta = $embeddeds[$embeddedMeta['paths'][$i]];

            try {
                $embedded = $this->readFromEmbedded($current, $parentMeta);
            } catch (UninitializedPropertyException $e) { // The property is not initialized
                $embedded = null;
            }

            if ($embedded === null) {
                if (!isset($parentMeta['class'])) {
                    return null;
                }

                $embedded = $this->instantiator->instantiate($parentMeta['class'], $parentMeta['hint']);

                // This writes should never fail : $embedded is not null
                $this->writeToEmbedded($current, $parentMeta, $embedded, false);
            }

            $current = $embedded;
        }

        return $embedded;
    }

    /**
     * Read a value from an entity, with attribute metadata
     *
     * @param object $entity
     * @param array $metadata
     *
     * @return mixed
     *
     * @throws ReflectionException When property do not exist on the object
     * @throws UninitializedPropertyException When the property is not initialized
     */
    protected function readFromAttribute($entity, array $metadata)
    {
        $attribute = $metadata['attribute'];
        $class = get_class($entity);

        if (isset($this->reflectionProperties[$class][$attribute])) {
            try {
                return $this->reflectionProperties[$class][$attribute]->getValue($entity);
            } catch (Error $e) {
                throw new UninitializedPropertyException($class, $this->reflectionProperties[$class][$attribute]->name, $e);
            }
        }

        if (!isset($metadata['embedded'])) {
            $property = $attribute;
        } else {
            $property = substr($attribute, strlen($metadata['embedded']) + 1);
        }

        return $this->readFromProperty($class, $property, $attribute, $entity);
    }

    /**
     * Read a value from an entity, with embedded metadata
     *
     * @param object $entity
     * @param array $metadata
     *
     * @return mixed
     *
     * @throws ReflectionException When property do not exist on the object
     * @throws UninitializedPropertyException When the property is not initialized
     */
    protected function readFromEmbedded($entity, array $metadata)
    {
        $attribute = $metadata['path'];
        $class = get_class($entity);

        if (isset($this->reflectionProperties[$class][$attribute])) {
            try {
                return $this->reflectionProperties[$class][$attribute]->getValue($entity);
            } catch (Error $e) {
                throw new UninitializedPropertyException($class, $this->reflectionProperties[$class][$attribute]->name, $e);
            }
        }

        if ($metadata['parentPath'] === 'root') {
            $property = $attribute;
        } else {
            $property = substr($attribute, strlen($metadata['parentPath']) + 1);
        }

        return $this->readFromProperty($class, $property, $attribute, $entity);
    }

    /**
     * @param object $entity
     * @param array $metadata
     * @param mixed $value
     *
     * @return void
     */
    protected function writeToAttribute($entity, array $metadata, $value, bool $skipInvalid)
    {
        $attribute = $metadata['attribute'];
        $class = get_class($entity);

        if (isset($this->reflectionProperties[$class][$attribute])) {
            $this->writeToReflection($this->reflectionProperties[$class][$attribute], $entity, $value, $skipInvalid, $metadata);
            return;
        }

        if (!isset($metadata['embedded'])) {
            $property = $attribute;
        } else {
            $property = substr($attribute, strlen($metadata['embedded']) + 1);
        }

        if ($class === stdClass::class) {
            $entity->{$property} = $value;
            return;
        }

        $this->reflectionProperties[$class][$attribute] = $reflectionProperty = new ReflectionProperty($class, $property);
        $reflectionProperty->setAccessible(true);

        $this->writeToReflection($reflectionProperty, $entity, $value, $skipInvalid, $metadata);
    }

    /**
     * @param object $entity
     * @param array $metadata
     * @param mixed $value
     *
     * @return void
     */
    protected function writeToEmbedded($entity, array $metadata, $value, bool $skipInvalid)
    {
        $attribute = $metadata['path'];
        $class = get_class($entity);

        if (isset($this->reflectionProperties[$class][$attribute])) {
            $this->writeToReflection($this->reflectionProperties[$class][$attribute], $entity, $value, $skipInvalid, $metadata);
            return;
        }

        if ($metadata['parentPath'] === 'root') {
            $property = $attribute;
        } else {
            $property = substr($attribute, strlen($metadata['parentPath']) + 1);
        }

        if ($class === stdClass::class) {
            $entity->{$property} = $value;
            return;
        }

        $this->reflectionProperties[$class][$attribute] = $reflectionProperty = new ReflectionProperty($class, $property);
        $reflectionProperty->setAccessible(true);

        $this->writeToReflection($reflectionProperty, $entity, $value, $skipInvalid, $metadata);
    }

    /**
     * Simple property read
     *
     * @param class-string $class
     * @param string $property
     * @param string $attribute
     * @param object $entity
     * @return mixed|null
     *
     * @throws ReflectionException
     * @throws UninitializedPropertyException
     */
    private function readFromProperty(string $class, string $property, string $attribute, $entity)
    {
        if ($class === stdClass::class) {
            return $entity->$property ?? null;
        }

        $this->reflectionProperties[$class][$attribute] = $propertyReflection = new ReflectionProperty($class, $property);
        $propertyReflection->setAccessible(true);

        try {
            return $propertyReflection->getValue($entity);
        } catch (Error $e) {
            throw new UninitializedPropertyException($class, $property, $e);
        }
    }

    /**
     * Check if the value should not be hydrated, in case of null value on not nullable property
     *
     * @param ReflectionProperty $property
     * @param mixed $value
     *
     * @return bool
     */
    private function shouldSkipValue(ReflectionProperty $property, $value): bool
    {
        if ($value !== null) {
            return false;
        }

        return $property->hasType() && !$property->getType()->allowsNull();
    }

    /**
     * @param ReflectionProperty $reflectionProperty
     * @param object $entity
     * @param mixed $value
     * @param bool $skipInvalid
     * @param array $metadata
     *
     * @throws InvalidTypeException
     *
     * @return void
     */
    private function writeToReflection(ReflectionProperty $reflectionProperty, $entity, $value, bool $skipInvalid, array $metadata): void
    {
        if (!$skipInvalid || !$this->shouldSkipValue($reflectionProperty, $value)) {
            try {
                $reflectionProperty->setValue($entity, $value);
            } catch (TypeError $e) {
                throw new InvalidTypeException($e, $metadata['type'] ?? null);
            }
        }
    }
}
