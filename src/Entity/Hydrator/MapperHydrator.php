<?php

namespace Bdf\Prime\Entity\Hydrator;

use Bdf\Prime\Entity\Instantiator\InstantiatorInterface;
use Bdf\Prime\Mapper\Metadata;
use Bdf\Prime\Platform\PlatformTypesInterface;
use stdClass;

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
     * @var \ReflectionProperty[][]
     */
    private $reflectionProperties = [];

    /**
     * {@inheritdoc}
     */
    public function setPrimeInstantiator(InstantiatorInterface $instantiator)
    {
        $this->instantiator = $instantiator;
    }

    /**
     * {@inheritdoc}
     */
    public function setPrimeMetadata(Metadata $metadata)
    {
        $this->metadata = $metadata;
    }

    /**
     * {@inheritdoc}
     */
    public function flatExtract($object, array $attributes = null)
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

            $values[$attribute] = $value;
        }

        return $values;
    }

    /**
     * {@inheritdoc}
     */
    public function flatHydrate($object, array $data, PlatformTypesInterface $types)
    {
        $metadata  = $this->metadata->fields;
        $embeddeds = $this->metadata->embeddeds;
        $cacheEmbedded = [
            'root' => $object
        ];

        foreach ($data as $field => $value) {
            if (!isset($metadata[$field])) {
                continue;
            }

            $value = $types->get($metadata[$field]['type'])->fromDatabase($value, $metadata[$field]['phpOptions']);

            if (isset($metadata[$field]['embedded'])) {
                $path = $metadata[$field]['embedded'];

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
                                $cacheEmbedded[$parentMeta['path']]
                            );
                        }
                    }
                }

                $this->writeToAttribute($cacheEmbedded[$path], $metadata[$field], $value);
            } else {
                $this->writeToAttribute($object, $metadata[$field], $value);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function extractOne($object, $attribute)
    {
        if (!isset($this->metadata->attributes[$attribute])) {
            if (!isset($this->metadata->embeddeds[$attribute])) {
                throw new \InvalidArgumentException('Cannot read from attribute "'.$attribute.'" : it\'s not declared');
            }

            return $this->readFromEmbedded($object, $this->metadata->embeddeds[$attribute]);
        } else {
            $ownerObject = $this->getOwnerObject($object, $this->metadata->attributes[$attribute]);

            // Polymorphic embedded not instantiated
            if ($ownerObject === null) {
                return null;
            }

            return $this->readFromAttribute($ownerObject, $this->metadata->attributes[$attribute]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function hydrateOne($object, $attribute, $value)
    {
        if (!isset($this->metadata->attributes[$attribute])) {
            if (!isset($this->metadata->embeddeds[$attribute])) {
                throw new \InvalidArgumentException('Cannot write to attribute "'.$attribute.'" : it\'s not declared');
            }

            $this->writeToEmbedded($object, $this->metadata->embeddeds[$attribute], $value);
        } else {
            $ownerObject = $this->getOwnerObject($object, $this->metadata->attributes[$attribute]);

            // Polymorphic embedded not instantiated
            if ($ownerObject === null) {
                throw new \InvalidArgumentException('Cannot write to attribute '.$attribute.' : the embedded entity cannot be resolved');
            }

            $this->writeToAttribute($ownerObject, $this->metadata->attributes[$attribute], $value);
        }
    }

    /**
     * Get owner object attribute
     *
     * @param object  $entity
     * @param array   $metadata  Metadata d'un attribut a retrouver
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

            $embedded = $this->readFromEmbedded($current, $parentMeta);

            if ($embedded === null) {
                if (!isset($parentMeta['class'])) {
                    return null;
                }

                $embedded = $this->instantiator->instantiate($parentMeta['class'], $parentMeta['hint']);

                $this->writeToEmbedded($current, $parentMeta, $embedded);
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
     */
    protected function readFromAttribute($entity, array $metadata)
    {
        $attribute = $metadata['attribute'];
        $class = get_class($entity);

        if (isset($this->reflectionProperties[$class][$attribute])) {
            return $this->reflectionProperties[$class][$attribute]->getValue($entity);
        }

        if (!isset($metadata['embedded'])) {
            $property = $attribute;
        } else {
            $property = substr($attribute, strlen($metadata['embedded']) + 1);
        }

        if ($class === stdClass::class) {
            return isset($entity->{$property}) ? $entity->{$property} : null;
        }

        $this->reflectionProperties[$class][$attribute] = new \ReflectionProperty($class, $property);
        $this->reflectionProperties[$class][$attribute]->setAccessible(true);
        return $this->reflectionProperties[$class][$attribute]->getValue($entity);
    }

    /**
     * Read a value from an entity, with embedded metadata
     *
     * @param object $entity
     * @param array $metadata
     *
     * @return mixed
     */
    protected function readFromEmbedded($entity, array $metadata)
    {
        $attribute = $metadata['path'];
        $class = get_class($entity);

        if (isset($this->reflectionProperties[$class][$attribute])) {
            return $this->reflectionProperties[$class][$attribute]->getValue($entity);
        }

        if ($metadata['parentPath'] === 'root') {
            $property = $attribute;
        } else {
            $property = substr($attribute, strlen($metadata['parentPath']) + 1);
        }

        if ($class === stdClass::class) {
            return isset($entity->{$property}) ? $entity->{$property} : null;
        }

        $this->reflectionProperties[$class][$attribute] = new \ReflectionProperty($class, $property);
        $this->reflectionProperties[$class][$attribute]->setAccessible(true);
        return $this->reflectionProperties[$class][$attribute]->getValue($entity);
    }

    /**
     * @param object $entity
     * @param array $metadata
     * @param mixed $value
     */
    protected function writeToAttribute($entity, array $metadata, $value)
    {
        $attribute = $metadata['attribute'];
        $class = get_class($entity);

        if (isset($this->reflectionProperties[$class][$attribute])) {
            $this->reflectionProperties[$class][$attribute]->setValue($entity, $value);
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

        $this->reflectionProperties[$class][$attribute] = new \ReflectionProperty($class, $property);
        $this->reflectionProperties[$class][$attribute]->setAccessible(true);
        $this->reflectionProperties[$class][$attribute]->setValue($entity, $value);
    }

    /**
     * @param object $entity
     * @param array $metadata
     * @param mixed $value
     */
    protected function writeToEmbedded($entity, array $metadata, $value)
    {
        $attribute = $metadata['path'];
        $class = get_class($entity);

        if (isset($this->reflectionProperties[$class][$attribute])) {
            $this->reflectionProperties[$class][$attribute]->setValue($entity, $value);
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

        $this->reflectionProperties[$class][$attribute] = new \ReflectionProperty($class, $property);
        $this->reflectionProperties[$class][$attribute]->setAccessible(true);
        $this->reflectionProperties[$class][$attribute]->setValue($entity, $value);
    }
}
