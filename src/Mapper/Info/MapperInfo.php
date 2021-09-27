<?php

namespace Bdf\Prime\Mapper\Info;

use Bdf\Prime\Mapper\Mapper;
use Bdf\Prime\Mapper\Metadata;
use Bdf\Prime\Types\TypesRegistryInterface;

/**
 * MapperInfo
 *
 * Cette classe est développer et utiliser pour l'entity generator.
 * Elle permet de simplifier la lecture des metadata.
 *
 * @todo remonter les traitements des classes du package Info sur les metadata, et completer
 * Pourrait etre util pour un hydrator
 */
class MapperInfo
{
    /**
     * The mapper
     *
     * @var Mapper
     */
    protected $mapper;

    /**
     * The types registry
     *
     * @var TypesRegistryInterface
     */
    protected $typesRegistry;

    /**
     * The metadata
     *
     * @var Metadata
     */
    protected $metadata;
    
    /**
     * The properties info
     * Contains only info about the root properties
     *
     * @var null|PropertyInfo[]
     */
    private $properties;

    /**
     * The primary properties
     *
     * @var null|PropertyInfo[]
     */
    private $primaries;

    /**
     * The properties info
     * Contains only info about the embeded properties
     *
     * @var null|PropertyInfo[]
     */
    private $embedded;
    
    /**
     * The properties info
     * Contains only info about the object properties
     *
     * @var null|ObjectPropertyInfo[]
     */
    private $objects;
    
    /**
     * Constructor
     * 
     * @param Mapper $mapper
     * @param TypesRegistryInterface $typesRegistry
     */
    public function __construct(Mapper $mapper, TypesRegistryInterface $typesRegistry = null)
    {
        $this->mapper = $mapper;
        $this->metadata = $mapper->metadata();
        $this->typesRegistry = $typesRegistry;
    }
    
    /**
     * Get the mapper
     * 
     * @return Mapper
     */
    public function mapper()
    {
        return $this->mapper;
    }
    
    /**
     * Get the metadata
     * 
     * @return Metadata
     */
    public function metadata()
    {
        return $this->metadata;
    }
    
    /**
     * Get the connection name
     * 
     * @return string|null
     */
    public function connection()
    {
        return $this->metadata->connection;
    }
    
    /**
     * Get the entity class name
     * 
     * @return string
     */
    public function className()
    {
        return $this->metadata->entityName;
    }
    
    /**
     * Get the entity properties
     * 
     * @return PropertyInfo[]
     */
    public function properties()
    {
        if ($this->properties === null) {
            $this->buildProperties();
        }
        
        return $this->properties;
    }
    
    /**
     * Get the primary properties
     * 
     * @return PropertyInfo[]
     */
    public function primaries()
    {
        if ($this->primaries === null) {
            $this->buildProperties();
        }
        
        return $this->primaries;
    }

    /**
     * Get the embedded properties
     *
     * @return PropertyInfo[]
     */
    public function embedded()
    {
        if ($this->embedded === null) {
            $this->buildProperties();
        }

        return $this->embedded;
    }

    /**
     * @psalm-assert !null $this->properties
     * @psalm-assert !null $this->embedded
     * @psalm-assert !null $this->primaries
     */
    private function buildProperties()
    {
        $this->properties = [];
        $this->embedded = [];
        $this->primaries = [];

        foreach ($this->metadata->attributes as $property => $metadata) {
            $this->buildProperty($property);
        }
    }

    /**
     * @param string $property
     * @return PropertyInfo|null
     */
    private function buildProperty(string $property): ?PropertyInfo
    {
        if (!isset($this->metadata->attributes[$property])) {
            return null;
        }

        $metadata = $this->metadata->attributes[$property];

        $info = new PropertyInfo($property, $metadata, $this->typesRegistry);

        if ($info->isPrimary()) {
            $this->primaries[$property] = $info;
        }

        if ($info->belongsToRoot()) {
            $this->properties[$property] = $info;
        } else {
            $this->embedded[$property] = $info;
        }

        return $info;
    }

    /**
     * Get the entity properties that are objects
     * 
     * @return ObjectPropertyInfo[]
     */
    public function objects()
    {
        if ($this->objects === null) {
            $this->buildObjectProperties();
        }
        
        return $this->objects;
    }

    /**
     * @psalm-assert !null $this->objects
     */
    private function buildObjectProperties()
    {
        $this->objects = [];
        $relations = $this->mapper->relations();
        
        foreach ($this->metadata->embeddeds as $property => $metadata) {
            $this->buildObjectProperty($property, $relations);
        }
    }

    /**
     * @param string $property
     * @param \ArrayAccess|array $relations
     * @return ObjectPropertyInfo|null
     */
    private function buildObjectProperty(string $property, $relations): ?ObjectPropertyInfo
    {
        if (!isset($this->metadata->embeddeds[$property])) {
            return null;
        }

        $metadata = $this->metadata->embeddeds[$property];
        $this->objects[$property] = new ObjectPropertyInfo($property, $metadata);

        if (isset($relations[$property])) {
            $this->objects[$property]->setRelation($relations[$property]);
        }

        return $this->objects[$property];
    }

    /**
     * Get all relations of the entity.
     *
     * @return ObjectPropertyInfo[]
     */
    public function relations()
    {
        $relations = [];

        foreach ($this->mapper->relations() as $property => $metadata) {
            $relations[$property] = new ObjectPropertyInfo($property);
            $relations[$property]->setRelation(is_array($metadata) ? $metadata : $metadata->relations());
        }

        return $relations;
    }
    
    /**
     * Get a properties
     * 
     * @return InfoInterface[]
     */
    public function all()
    {
        return $this->properties() + $this->objects();
    }
    
    /**
     * Get a property info
     * 
     * @param string $name
     * 
     * @return null|InfoInterface
     */
    public function property($name)
    {
        return $this->properties[$name]
            ?? $this->objects[$name]
            ?? $this->embedded[$name]
            ?? $this->buildProperty($name)
            ?? $this->buildObjectProperty($name, $this->mapper->relations());
    }
}
