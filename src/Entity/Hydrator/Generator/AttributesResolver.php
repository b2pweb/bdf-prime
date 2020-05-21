<?php

namespace Bdf\Prime\Entity\Hydrator\Generator;

use Bdf\Prime\Entity\ImportableInterface;
use Bdf\Prime\Exception\HydratorException;
use Bdf\Prime\Mapper\Mapper;
use Bdf\Prime\Mapper\SingleTableInheritanceMapper;
use Bdf\Prime\Relations\RelationInterface;
use Bdf\Prime\ServiceLocator;

/**
 * Resolve attributes and embedded objects from Mapper
 */
class AttributesResolver
{
    /**
     * @var Mapper
     */
    private $mapper;

    /**
     * @var ServiceLocator
     */
    private $prime;

    /**
     * List of all attributes of the entity
     *
     * @var AttributeInfo[]
     */
    private $attributes = [];

    /**
     * List of all embedded entities
     *
     * @var EmbeddedInfo[]
     */
    private $embeddeds = [];

    /**
     * @var AttributeInfo[]
     */
    private $rootAttributes = [];

    /**
     * @var EmbeddedInfo[]
     */
    private $rootEmbeddeds = [];


    /**
     * AttributesResolver constructor.
     *
     * @param Mapper $mapper
     * @param ServiceLocator $prime
     *
     * @thorws HydratorException
     */
    public function __construct(Mapper $mapper, ServiceLocator $prime)
    {
        $this->mapper = $mapper;
        $this->prime = $prime;

        $this->build();
    }

    /**
     * @return AttributeInfo[]
     */
    public function attributes()
    {
        return $this->attributes;
    }


    /**
     * @param string $name
     *
     * @return AttributeInfo
     */
    public function attribute($name)
    {
        return $this->attributes[$name];
    }

    /**
     * @return EmbeddedInfo[]
     */
    public function embeddeds()
    {
        return $this->embeddeds;
    }

    /**
     * Get the embedded entity info
     *
     * @param string $attribute The attribute path
     *
     * @return EmbeddedInfo
     */
    public function embedded($attribute)
    {
        return $this->embeddeds[$attribute];
    }

    /**
     * Get the embedded entity info
     *
     * @param string $attribute The attribute path
     *
     * @return EmbeddedInfo
     */
    public function rootEmbedded($attribute)
    {
        return $this->rootEmbeddeds[$attribute];
    }

    /**
     * Get all "root" embeddeds
     *
     * @return EmbeddedInfo[]
     */
    public function rootEmbeddeds()
    {
        return $this->rootEmbeddeds;
    }

    /**
     * Check if the root embedded exists
     *
     * @param string $attribute The attribute path
     *
     * @return bool
     */
    public function hasRootEmbedded($attribute)
    {
        return isset($this->rootEmbeddeds[$attribute]);
    }

    /**
     * Get all the "root" attributes (not defined into an embedded, or the root embedded property)
     *
     * @return AttributeInfo[]
     */
    public function rootAttributes()
    {
        return $this->rootAttributes;
    }

    /**
     * Does the class is an entity
     * Means could have a hydrator
     *
     * @param string $class
     *
     * @return bool
     */
    public function isEntity($class)
    {
        return $this->prime->mappers()->isEntity($class);
    }

    /**
     * Does the class implements importable interface
     *
     * @param string $class
     *
     * @return bool
     */
    public function isImportable($class)
    {
        return is_subclass_of($class, ImportableInterface::class);
    }

    /**
     * Build the attributes and embedded info
     *
     * @thows HydratorException
     */
    private function build()
    {
        $this->buildMetadataProperties();
        $this->buildRootAttributes();
        $this->buildRootEmbeddeds();
        $this->buildRelationEmbeddeds();
    }

    private function buildMetadataProperties()
    {
        foreach ($this->mapper->metadata()->attributes() as $name => $meta) {
            $this->attributes[$name] = new AttributeInfo($name, $meta, $this);
        }

        foreach ($this->mapper->metadata()->embeddeds() as $name => $meta) {
            $this->embeddeds[$name] = new EmbeddedInfo($name, $meta, $this);
        }
    }

    private function buildRootAttributes()
    {
        foreach ($this->attributes as $attribute) {
            if ($attribute->isEmbedded()) {
                //Find only the "root" embedded attribute
                $rootAttribue = $attribute->embedded()->rootAttribute();

                if (isset($this->rootAttributes[$rootAttribue])) {
                    continue;
                }

                $attribute = $this->attributes[$rootAttribue] ?? new AttributeInfo($rootAttribue, [
                    'embedded' => $rootAttribue,
                    'root'     => true,
                ], $this);
            }

            $this->rootAttributes[$attribute->name()] = $attribute;
        }
    }

    private function buildRootEmbeddeds()
    {
        foreach ($this->embeddeds as $embedded) {
            if ($embedded->isRoot() && ($embedded->isEntity() || $embedded->isImportable())) {
                $this->rootEmbeddeds[$embedded->path()] = $embedded;
            }
        }
    }

    /**
     * @throws HydratorException
     */
    private function buildRelationEmbeddeds()
    {
        foreach ($this->mapper->relations()->relations() as $name => $relation) {
            if (!empty($relation['detached'])) {
                unset($this->rootEmbeddeds[$name]);
                continue;
            }

            // "MANY" relations are stored as array, so cannot be hydrated
            if (!in_array($relation['type'], [RelationInterface::HAS_MANY, RelationInterface::BELONGS_TO_MANY])) {
                $this->rootEmbeddeds[$name] = new EmbeddedInfo($name, [
                    'parentPath' => 'root',
                    'path'       => $name,
                    'paths'      => [$name],
                    'classes'    => $this->resolveClassesFromRelation($relation, $name),
                ], $this);
            } elseif (
                !empty($relation['wrapper'])
                && is_subclass_of($wrapperClass = $this->prime->repository($relation['entity'])->collectionFactory()->wrapperClass($relation['wrapper']), ImportableInterface::class)
            ) {
                $this->rootEmbeddeds[$name] = new EmbeddedInfo($name, [
                    'parentPath' => 'root',
                    'path'       => $name,
                    'paths'      => [$name],
                    'class'      => $wrapperClass,
                ], $this);
            } else {
                unset($this->rootEmbeddeds[$name]);
            }

            if (!isset($this->rootAttributes[$name])) {
                $attributeMetadata = ['root' => true];

                if (isset($this->rootEmbeddeds[$name])) {
                    $attributeMetadata['embedded'] = $name;
                }

                $this->rootAttributes[$name] = new AttributeInfo($name, $attributeMetadata, $this);
            }
        }
    }

    /**
     * Resolve entities classes from one relation
     *
     * @param array $relation The relation metadata
     * @param string $relationName The relation name
     *
     * @return string[] List of entities classes
     *
     * @throws HydratorException
     */
    private function resolveClassesFromRelation(array $relation, $relationName)
    {
        switch ($relation['type']) {
            case RelationInterface::HAS_ONE:
            case RelationInterface::BELONGS_TO:
                return [$relation['entity']];

            case RelationInterface::MORPH_TO:
                $classes = [];

                foreach ($relation['map'] as $name => $entity) {
                    if (is_array($entity)) {
                        $classes[] = $entity['entity'];
                    } else {
                        $classes[] = explode('::', $entity, 2)[0];
                    }
                }

                return $classes;

            case RelationInterface::BY_INHERITANCE:
                if (!$this->mapper instanceof SingleTableInheritanceMapper) {
                    throw new HydratorException($this->mapper->getEntityClass(), "The mapper should be a subclass of SingleTableInheritanceMapper for use 'by inheritance' relation");
                }

                $classes = [];

                foreach ($this->mapper->getEntityMap() as $entityClass) {
                    $subMapper = $this->prime->mappers()->build($this->prime, $entityClass);
                    $classes = array_merge($classes, $this->resolveClassesFromRelation($subMapper->relations()->relations()[$relationName], $relationName));
                }

                return array_unique($classes);

            default:
                throw new HydratorException($this->mapper->getEntityClass(), 'Cannot handle relation type ' . $relation['type']);
        }
    }
}
